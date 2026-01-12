<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\AddToCartRequest;
use App\Http\Requests\Front\ApplyPromoRequest;
use App\Http\Requests\Front\UpdateCartItemRequest;
use App\Models\Catalog\ProductVariant;
use App\Models\Commerce\CartItem;
use App\Models\Support\PromoCode;
use App\Models\Legacy\Repair;
use App\Services\Commerce\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {
    }

    public function show(Request $request)
    {
        $cart = $this->cartService
            ->resolveCart($request->user())
            ->load(['items.variant.product', 'items.product', 'promoCode']);

        // Le panier n'affiche pas d'options de livraison (elles sont calculées au checkout).
        // Évite aussi des appels Boxtal + écritures session potentiellement lourdes.
        $options = collect();

        return view('front.cart.index', [
            'cart' => $cart,
            'shippingOptions' => $options,
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->resolveCart($request->user());
        $context = $request->validated();

        if ($request->filled('repair_id')) {
            $repair = Repair::findOrFail($request->integer('repair_id'));

            if (! $repair->supplier_stock || (int) $repair->supplier_stock <= 0) {
                return back()->withErrors(['repair_id' => 'Produit actuellement en rupture.'])->withInput();
            }

            $variant = $repair->ensureVariant();
            $context['image_url'] = $repair->image_fallback;
        } else {
            $variant = ProductVariant::with('product')->findOrFail($request->integer('variant_id'));
        }

        $variant->loadMissing('product');

        $this->cartService->addItem($cart, $variant, $request->integer('quantity', 1), $context);

        $cart->refresh()->load('items');
        $cartCount = $cart->items->sum('quantity');
        $cartTotal = $cart->subtotal_ttc;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cartCount' => $cartCount,
                'cartTotal' => $cartTotal,
                'message' => 'Produit ajouté au panier.',
            ]);
        }

        return back()->with('success', 'Produit ajouté au panier.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);
        $this->cartService->updateItem($item, $request->integer('quantity'));

        return back()->with('success', 'Quantité mise à jour.');
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);
        $this->cartService->removeItem($item);

        return back()->with('success', 'Produit retiré du panier.');
    }

    public function applyPromo(ApplyPromoRequest $request): RedirectResponse
    {
        $cart = $this->cartService->resolveCart($request->user());
        $current = $cart->promoCode;
        $inputCode = trim($request->string('code')->toString());
        if ($inputCode === '' && $current) {
            $this->cartService->refreshTotals($cart->fresh(['items', 'promoCode']));
            return back()->with('success', 'Code promo déjà appliqué.');
        }
        // Tolérance sur d'éventuels espaces en base (copier/coller admin)
        $promo = PromoCode::whereRaw('LOWER(TRIM(code)) = ?', [strtolower($inputCode)])->first();

        if (! $promo) {
            return back()->withErrors(['code' => 'Ce code promo est introuvable.'])->withInput();
        }

        if (! $promo->is_active) {
            return back()->withErrors(['code' => 'Ce code promo est desactive.'])->withInput();
        }

        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            return back()->withErrors([
                'code' => 'Ce code promo sera actif a partir du '.$promo->starts_at->format('d/m/Y H:i').'.',
            ])->withInput();
        }

        if ($promo->ends_at && $promo->ends_at->isPast()) {
            return back()->withErrors([
                'code' => 'Ce code promo a expire le '.$promo->ends_at->format('d/m/Y H:i').'.',
            ])->withInput();
        }

        if ($promo->applies_to_pro && ! $request->user()?->isPro()) {
            // Ne touche pas au code actuel si invalide
            return back()->withErrors(['code' => 'Ce code promo est reserve aux professionnels.'])->withInput();
        }

        // Si le même code est déjà appliqué, on garde et on recalcule juste au cas où
        if ($current && strcasecmp($current->code, $promo->code) === 0) {
            $this->cartService->refreshTotals($cart->fresh(['items', 'promoCode']));
            return back()->with('success', 'Code promo déjà appliqué.');
        }

        $this->cartService->applyPromo($cart, $promo);

        $message = $current && strcasecmp($current->code, $promo->code) !== 0
            ? "Code {$promo->code} appliqué à la place de {$current->code}."
            : 'Code promo appliqué.';

        return back()->with('success', $message);
    }

    public function removePromo(Request $request): RedirectResponse
    {
        $cart = $this->cartService->resolveCart($request->user());
        $cart->update(['promo_code_id' => null]);
        $this->cartService->refreshTotals($cart->fresh(['items', 'promoCode']));

        return back()->with('success', 'Code promo retiré.');
    }

    private function authorizeItem(CartItem $item): void
    {
        $tokenMatches = $item->cart->token === session('cart_token');
        $userMatches = auth()->check() && $item->cart->user_id === auth()->id();

        if (! $tokenMatches && ! $userMatches) {
            abort(403);
        }
    }
}
