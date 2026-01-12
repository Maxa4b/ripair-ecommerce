<?php

namespace App\Services\Commerce;

use App\Models\Catalog\ProductVariant;
use App\Models\Commerce\Cart;
use App\Models\Commerce\CartItem;
use App\Models\Legacy\Repair;
use App\Models\Support\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public function __construct(
        protected PricingService $pricing,
    ) {
    }

    public function resolveCart(?User $user, ?string $token = null): Cart
    {
        if ($user?->cart) {
            session()->put('cart_token', $user->cart->token);
            return $user->cart->load('items.variant.product', 'items.product');
        }

        $token ??= session()->get('cart_token');

        if ($token) {
            $cart = Cart::firstWhere('token', $token);
            if ($cart) {
                session()->put('cart_token', $cart->token);
                return $cart->load('items.variant.product', 'items.product');
            }
        }

        $cart = Cart::create([
            'user_id' => $user?->id,
            'token' => Str::uuid()->toString(),
            'currency' => 'EUR',
        ]);

        session()->put('cart_token', $cart->token);

        return $cart->load('items.variant.product', 'items.product');
    }

    public function claimSessionCartFor(User $user): Cart
    {
        $sessionToken = session()->get('cart_token');

        $userCart = $user->cart()->first();
        $sessionCart = $sessionToken ? Cart::where('token', $sessionToken)->first() : null;

        // Si le token session ne pointe vers rien, on retombe sur le panier du compte.
        if (! $sessionCart) {
            if ($userCart) {
                session()->put('cart_token', $userCart->token);
                $this->repriceCartForUser($userCart, $user);

                return $userCart->load('items.variant.product', 'items.product', 'promoCode');
            }

            $cart = Cart::create([
                'user_id' => $user->id,
                'token' => Str::uuid()->toString(),
                'currency' => 'EUR',
            ]);
            session()->put('cart_token', $cart->token);

            return $cart->load('items.variant.product', 'items.product', 'promoCode');
        }

        // Si ce panier appartient déjà à un autre utilisateur, on ne touche pas.
        if ($sessionCart->user_id && (int) $sessionCart->user_id !== (int) $user->id) {
            if ($userCart) {
                session()->put('cart_token', $userCart->token);
                $this->repriceCartForUser($userCart, $user);

                return $userCart->load('items.variant.product', 'items.product', 'promoCode');
            }

            return $this->resolveCart($user);
        }

        // Si l'utilisateur avait déjà un panier différent, on le fusionne dans le panier de session (celui en cours).
        if ($userCart && $userCart->id !== $sessionCart->id) {
            DB::transaction(function () use ($sessionCart, $userCart): void {
                $sessionCart->loadMissing('items');
                $userCart->loadMissing('items');

                $byVariant = $sessionCart->items->keyBy('product_variant_id');
                foreach ($userCart->items as $item) {
                    $target = $byVariant->get($item->product_variant_id);
                    if ($target) {
                        $target->increment('quantity', (int) $item->quantity);
                    } else {
                        $item->update(['cart_id' => $sessionCart->id]);
                    }
                }

                // Conserve un code promo si le panier de session n'en a pas.
                if (! $sessionCart->promo_code_id && $userCart->promo_code_id) {
                    $sessionCart->promo_code_id = $userCart->promo_code_id;
                }

                $sessionCart->save();

                // Détache l'ancien panier du compte pour éviter 2 paniers avec le même user_id.
                $userCart->update(['user_id' => null]);
            });
        }

        $sessionCart->update(['user_id' => $user->id]);
        session()->put('cart_token', $sessionCart->token);

        $this->persistCheckoutSessionToCart($sessionCart);
        $this->repriceCartForUser($sessionCart, $user);

        return $sessionCart->load('items.variant.product', 'items.product', 'promoCode');
    }

    public function addItem(Cart $cart, ProductVariant $variant, int $quantity, array $options = []): CartItem
    {
        return DB::transaction(function () use ($cart, $variant, $quantity, $options): CartItem {
            $cart->loadMissing('user');
            $variant->loadMissing('product');
            $existing = $cart->items()
                ->where('product_variant_id', $variant->id)
                ->first();

            $priceTtc = $this->pricing->bestPriceForUser($variant->price_ttc, $cart->user);
            $priceHt = $this->pricing->bestPriceForUser($variant->price_ht, $cart->user);
            $snapshotImage = $this->resolveVariantImage($variant, $options);
            $variantSnapshot = [
                'color' => $variant->color,
                'quality' => $variant->quality,
                'revision' => $variant->revision,
                'image_url' => $snapshotImage,
            ];

            if ($existing) {
                $existing->increment('quantity', $quantity);
                $needsImage = $snapshotImage && empty(data_get($existing->variant_snapshot, 'image_url'));
                if ($needsImage) {
                    $existing->update([
                        'variant_snapshot' => array_merge($existing->variant_snapshot ?? [], ['image_url' => $snapshotImage]),
                    ]);
                }
                $item = $existing->refresh();
            } else {
                $item = $cart->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'reference' => $variant->product->internal_reference,
                    'variant_snapshot' => $variantSnapshot,
                    'quantity' => $quantity,
                    'unit_price_ht' => $priceHt,
                    'unit_price_ttc' => $priceTtc,
                    'tax_rate' => $variant->tax_rate,
                    'discount_total' => 0,
                ]);
            }

            $this->refreshTotals($cart->fresh(['items', 'promoCode']));

            return $item;
        });
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        $item->update(['quantity' => max(1, $quantity)]);
        $this->refreshTotals($item->cart->fresh(['items', 'promoCode']));

        return $item->refresh();
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $item->cart;
        $item->delete();
        $this->refreshTotals($cart->fresh(['items', 'promoCode']));
    }

    public function applyPromo(Cart $cart, PromoCode $promo): void
    {
        $cart->promo_code_id = $promo->id;
        $cart->save();
        $this->refreshTotals($cart->fresh(['items', 'promoCode']));
    }

    private function resolveVariantImage(ProductVariant $variant, array $options = []): ?string
    {
        $explicit = $options['image_url'] ?? null;
        if ($explicit) {
            return $explicit;
        }

        $imagePath = $variant->product->image_url ?? null;

        if (! $imagePath && ($legacyId = data_get($variant->attributes, 'legacy_repair_id'))) {
            $legacy = Repair::find($legacyId);
            $imagePath = $legacy?->image_fallback;
        }

        if (! $imagePath) {
            return null;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://') || str_starts_with($imagePath, '//')) {
            return $imagePath;
        }

        return asset($imagePath);
    }

    public function refreshTotals(Cart $cart): Cart
    {
        // S'assure que les relations nécessaires sont chargées pour calculer remises/promo
        $cart->loadMissing('items', 'promoCode');
        $totals = $this->pricing->calculateCartTotals($cart);

        $cart->fill($totals)->save();

        return $cart;
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update([
            'promo_code_id' => null,
            'subtotal_ht' => 0,
            'subtotal_ttc' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'metadata' => null,
            // Le statut ne doit contenir qu'une valeur autorisée par l'ENUM
            'status' => 'converted',
        ]);
        session()->put('cart_token', $cart->token);
    }

    private function repriceCartForUser(Cart $cart, User $user): void
    {
        $cart->loadMissing('items.variant.product', 'promoCode');

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            if (! $variant) {
                continue;
            }

            $priceTtc = $this->pricing->bestPriceForUser((float) $variant->price_ttc, $user);
            $priceHt = $this->pricing->bestPriceForUser((float) $variant->price_ht, $user);

            if ((float) $item->unit_price_ttc !== (float) $priceTtc || (float) $item->unit_price_ht !== (float) $priceHt) {
                $item->update([
                    'unit_price_ht' => $priceHt,
                    'unit_price_ttc' => $priceTtc,
                ]);
            }
        }

        $this->refreshTotals($cart->fresh(['items', 'promoCode']));
    }

    private function persistCheckoutSessionToCart(Cart $cart): void
    {
        $checkoutData = session('checkout', []);
        if (! is_array($checkoutData) || empty($checkoutData)) {
            return;
        }

        $metadata = is_array($cart->metadata) ? $cart->metadata : [];
        if (empty($metadata['checkout_started_at'])) {
            $metadata['checkout_started_at'] = now()->toISOString();
        }
        $metadata['checkout'] = $checkoutData;
        $metadata['checkout_updated_at'] = now()->toISOString();

        $cart->update(['metadata' => $metadata]);
    }
}
