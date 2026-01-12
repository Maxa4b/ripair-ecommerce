<?php

namespace App\Services\Logistics;

use App\Models\Commerce\Cart;
use App\Models\Logistics\ShippingMethod;
use App\Services\Logistics\BoxtalService;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use RuntimeException;

class ShippingService
{
    public function optionsForCart(Cart $cart, array $address): Collection
    {
        $weight = $cart->items->sum(fn ($item) => optional($item->variant?->product)->shipping_weight ?? 0.2);
        $total = $cart->subtotal_ttc;
        $boxtalOptions = collect();
        $freeShippingMin = (float) (config('pricing.free_shipping_min_total') ?? 0);
        // Eligibilité calculée sur le panier avant remise pour ne pas annuler la livraison offerte
        $eligibleBase = max(0, ($cart->subtotal_ttc ?? 0));
        $hasShippingPromo = $cart->promoCode && $cart->promoCode->discount_type === 'shipping';
        $isFreeShipping = $hasShippingPromo || ($freeShippingMin > 0 && $eligibleBase >= $freeShippingMin);
        $toAddress = [
            'country' => data_get($address, 'country_code'),
            'zip' => data_get($address, 'postal_code'),
            'city' => data_get($address, 'city'),
            'type' => data_get($address, 'type', 'particulier'),
        ];
        $originCfg = config('services.boxtal.from', []);
        $fromAddress = [
            'country' => data_get($originCfg, 'country', 'FR'),
            'zip' => data_get($originCfg, 'zip'),
            'city' => data_get($originCfg, 'city'),
            'type' => data_get($originCfg, 'type', 'entreprise'),
            'height' => config('services.boxtal.defaults.height', 20),
            'width' => config('services.boxtal.defaults.width', 20),
            'length' => config('services.boxtal.defaults.length', 20),
        ];

        // Prépare les colis (si plusieurs items) avec dimensions par défaut
        $defaults = config('services.boxtal.defaults', []);
        $length = $defaults['length'] ?? 20;
        $width = $defaults['width'] ?? 20;
        $height = $defaults['height'] ?? 20;
        $contentCode = $defaults['content_code'] ?? '10150';

        $packages = [];
        foreach ($cart->items as $item) {
            $pkgWeight = optional($item->variant?->product)->shipping_weight ?? 0.2;
            $pkgLength = optional($item->variant?->product)->shipping_length ?? $length;
            $pkgWidth = optional($item->variant?->product)->shipping_width ?? $width;
            $pkgHeight = optional($item->variant?->product)->shipping_height ?? $height;
            for ($i = 0; $i < $item->quantity; $i++) {
                $packages[] = [
                    'weight' => max(0.2, (float) $pkgWeight),
                    'length' => (float) $pkgLength,
                    'width' => (float) $pkgWidth,
                    'height' => (float) $pkgHeight,
                    'value' => $total, // valeur déclarée globale
                ];
            }
        }

        // Cache session courte : évite de rappeler Boxtal si le panier + adresse n'ont pas changé.
        $cacheKey = hash('sha256', json_encode([
            'token' => (string) ($cart->token ?? ''),
            'items' => $cart->items
                ->map(fn ($i) => [(int) ($i->product_variant_id ?? 0), (int) ($i->quantity ?? 0)])
                ->values()
                ->all(),
            'subtotal_ttc' => (float) ($cart->subtotal_ttc ?? 0),
            'address' => [
                'country' => (string) ($toAddress['country'] ?? ''),
                'zip' => (string) ($toAddress['zip'] ?? ''),
                'city' => (string) ($toAddress['city'] ?? ''),
                'type' => (string) ($toAddress['type'] ?? ''),
            ],
            'free_shipping' => $isFreeShipping ? 1 : 0,
            'content_code' => (string) $contentCode,
        ]));

        $cachedQuotes = session('boxtal_quotes');
        $cachedKey = session('boxtal_quotes_key');
        $cachedAt = (int) (session('boxtal_quotes_cached_at') ?? 0);
        $ttlSeconds = 15 * 60;
        if (is_array($cachedQuotes) && $cachedKey === $cacheKey && $cachedAt > 0 && (time() - $cachedAt) < $ttlSeconds) {
            return collect($cachedQuotes)->values();
        }

        $boxtal = BoxtalService::make();
        if ($boxtal->enabled() && ! empty($toAddress['country']) && ! empty($toAddress['zip'])) {
            $boxtalOptions = $boxtal->quotes($fromAddress, $toAddress, $packages, $contentCode)
                ->map(function ($quote) {
                    return array_merge($quote, [
                        'type' => $quote['type'] ?? 'shipping',
                        'origin' => 'boxtal',
                    ]);
                });
            if ($isFreeShipping) {
                $boxtalOptions = $boxtalOptions->map(function ($quote) {
                    $name = $quote['name'] ?? ($quote['service'] ?? 'Livraison');
                    $label = str_contains(strtolower($name), 'offerte') ? $name : $name.' (offerte)';
                    $original = (float) ($quote['price_original'] ?? $quote['price'] ?? 0);

                    return array_merge($quote, [
                        'name' => $label,
                        'price' => 0.0,
                        'price_original' => $original,
                        'is_free' => $original > 0,
                    ]);
                });
            }
	            if ($boxtalOptions->isNotEmpty()) {
	                session()->put('boxtal_quotes', $boxtalOptions->keyBy('method_id')->toArray());
	                session()->put('boxtal_quotes_key', $cacheKey);
	                session()->put('boxtal_quotes_cached_at', time());
	            }
        } else {
            session()->put('boxtal_debug', 'Boxtal désactivé ou adresse incomplète');
        }

	        if ($boxtalOptions->isNotEmpty()) {
	            session()->put('boxtal_quotes', $boxtalOptions->keyBy('method_id')->toArray());
	            session()->put('boxtal_quotes_key', $cacheKey);
	            session()->put('boxtal_quotes_cached_at', time());
	            return $boxtalOptions->values();
	        }

        // Fallback local si Boxtal est indisponible pour ne pas bloquer le checkout
	        $fallback = collect([$this->fallbackOption($cart, $isFreeShipping)]);
	        session()->put('boxtal_quotes', $fallback->keyBy('method_id')->toArray());
	        session()->put('boxtal_quotes_key', $cacheKey);
	        session()->put('boxtal_quotes_cached_at', time());
        session()->put('boxtal_debug', 'Boxtal indisponible : fallback local appliqué');

        return $fallback;
    }

    public function findOption(string|int $methodId): object
    {
        // Boxtal option
        if (is_string($methodId) && str_starts_with($methodId, 'boxtal:')) {
            $quotes = collect(session('boxtal_quotes', []));
            $quote = $quotes->get($methodId);
            if (! $quote) {
                throw new RuntimeException('Mode de livraison Boxtal introuvable.');
            }

            return (object) [
                'method_id' => null,
                'type' => $quote['type'] ?? 'shipping',
                'price' => $quote['price'],
                'provider' => 'boxtal',
                'metadata' => [
                    'method_id' => $methodId,
                    'name' => $quote['name'] ?? null,
                    'operator' => $quote['operator'] ?? null,
                    'service' => $quote['service'] ?? null,
                    'delay' => $quote['delay'] ?? null,
                    'origin' => 'boxtal',
                    'price_original' => $quote['price_original'] ?? $quote['price'] ?? null,
                    'is_free' => $quote['is_free'] ?? false,
                ],
            ];
        }

        // Fallback local (manual:standard)
        if (is_string($methodId) && str_starts_with($methodId, 'manual:')) {
            $quotes = collect(session('boxtal_quotes', []));
            $quote = $quotes->get($methodId);
            if (! $quote) {
                throw new RuntimeException('Mode de livraison fallback introuvable.');
            }

            return (object) [
                'method_id' => null,
                'type' => $quote['type'] ?? 'shipping',
                'price' => $quote['price'],
                'provider' => $quote['provider'] ?? 'manual',
                'metadata' => [
                    'origin' => $quote['origin'] ?? 'fallback',
                    'method_id' => $methodId,
                    'name' => $quote['name'] ?? null,
                    'operator' => $quote['operator'] ?? null,
                    'service' => $quote['service'] ?? null,
                    'price_original' => $quote['price_original'] ?? $quote['price'] ?? null,
                    'is_free' => $quote['is_free'] ?? false,
                ],
            ];
        }

        throw new RuntimeException('Mode de livraison introuvable.');
    }

    private function matchesRate($rate, float $weight, float $total): bool
    {
        return $weight >= $rate->min_weight
            && ($rate->max_weight === null || $weight <= $rate->max_weight)
            && $total >= $rate->min_total
            && ($rate->max_total === null || $total <= $rate->max_total);
    }

    private function formatDelay(ShippingMethod $method): string
    {
        if (!empty($method->delivery_time)) {
            return $method->delivery_time;
        }

        $min = $method->min_delay_days ?? null;
        $max = $method->max_delay_days ?? null;

        if ($min && $max) {
            return "{$min} - {$max} jours";
        }
        if ($min) {
            return "{$min} jours";
        }
        if ($max) {
            return "{$max} jours";
        }

        return '';
    }

    private function fallbackOption(Cart $cart, bool $forceFree = false): array
    {
        $freeShippingMin = config('pricing.free_shipping_min_total', 0);
        $smallOrderFee = config('pricing.small_order_shipping_fee', 0);
        // Eligibilité calculée sur le panier avant remise pour ne pas annuler la livraison offerte
        $eligibleBase = max(0, ($cart->subtotal_ttc ?? 0));
        $hasShippingPromo = $cart->promoCode && $cart->promoCode->discount_type === 'shipping';
        $price = ($forceFree || $hasShippingPromo || ($freeShippingMin > 0 && $eligibleBase >= $freeShippingMin)) ? 0 : $smallOrderFee;

        return [
            'method_id' => 'manual:standard',
            'name' => $price <= 0 ? 'Livraison standard (offerte)' : 'Livraison standard',
            'price' => round((float) $price, 2),
            'price_original' => round((float) $smallOrderFee, 2),
            'is_free' => ($price <= 0 && (float) $smallOrderFee > 0),
            'delay' => '2 - 4 jours ouvrés',
            'type' => 'shipping',
            'origin' => 'fallback',
            'provider' => 'manual',
        ];
    }
}
