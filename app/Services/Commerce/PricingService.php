<?php

namespace App\Services\Commerce;

use App\Models\Commerce\Cart;
use App\Models\Support\ProPricingRule;
use App\Models\User;
use Illuminate\Support\Collection;

class PricingService
{
    public function calculateCartTotals(Cart $cart): array
    {
        $items = $cart->items;

        $subtotalHt = $items->sum(fn ($item) => $item->unit_price_ht * $item->quantity);
        $subtotalTtc = $items->sum(fn ($item) => $item->unit_price_ttc * $item->quantity);
        $taxTotal = $subtotalTtc - $subtotalHt;
        $discountTotal = $items->sum('discount_total');
        $shippingTotal = $cart->shipping_total ?? 0;
        $originalShippingTotal = $shippingTotal;
        $shippingSurcharge = (float) (config('pricing.shipping_surcharge') ?? 0);

        $hasForcedShipping = method_exists($cart, 'forcedShippingFee') && $cart->forcedShippingFee() !== null;
        $shippingDiscountCap = $hasForcedShipping ? 0 : max(0, $shippingTotal - $shippingSurcharge);

        if ($cart->promoCode) {
            $promoDiscount = $this->calculatePromoDiscount($cart, $subtotalTtc, $shippingDiscountCap);
            $discountTotal += $promoDiscount;

            // Si le code concerne la livraison, on annule le montant de livraison
            if ($cart->promoCode->discount_type === 'shipping' && ! $hasForcedShipping) {
                $shippingTotal = max($shippingSurcharge, 0);
            }
        }

        // Empêche une remise de dépasser le panier (items + expédition)
        $maxDiscount = $subtotalTtc + $originalShippingTotal;
        $discountTotal = min($discountTotal, $maxDiscount);

        return [
            'subtotal_ht' => $subtotalHt,
            'subtotal_ttc' => $subtotalTtc,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
        ];
    }

    public function bestPriceForUser(float $price, ?User $user): float
    {
        if ($user?->isPro() && $user->pro_discount_rate > 0) {
            $price -= $price * ($user->pro_discount_rate / 100);
        }

        return round($price, 2);
    }

    public function proRulesForCategory(int $categoryId): Collection
    {
        return ProPricingRule::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($categoryId): void {
                $builder
                    ->whereNull('category_id')
                    ->orWhere('category_id', $categoryId);
            })
            ->orderByDesc('discount_value')
            ->get();
    }

    private function calculatePromoDiscount(Cart $cart, float $currentSubtotalTtc, float $currentShipping): float
    {
        $promo = $cart->promoCode;

        return match ($promo->discount_type) {
            'percentage' => round($currentSubtotalTtc * ($promo->value / 100), 2),
            'fixed' => min($currentSubtotalTtc, $promo->value),
            'shipping' => $currentShipping,
            default => 0,
        };
    }
}
