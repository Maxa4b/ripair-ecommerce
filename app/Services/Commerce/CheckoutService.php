<?php

namespace App\Services\Commerce;

use App\Models\Commerce\Cart;
use App\Models\Commerce\Order;
use App\Models\Commerce\OrderItem;
use App\Models\Commerce\Payment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Support\Address;
use App\Models\User;
use App\Services\Logistics\ShippingService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected PricingService $pricing,
        protected ShippingService $shipping,
        protected PaymentGatewayManager $payments,
        protected InvoiceService $invoices,
        protected StockService $stock,
    ) {
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = is_string($email) ? trim($email) : null;
        if (! $email) {
            return null;
        }

        return mb_strtolower($email);
    }

    private function sanitizeAddressForOrder(array $data, ?string $email = null): array
    {
        return [
            'first_name' => Arr::get($data, 'first_name'),
            'last_name' => Arr::get($data, 'last_name'),
            'company' => Arr::get($data, 'company'),
            'line1' => Arr::get($data, 'line1'),
            'line2' => Arr::get($data, 'line2'),
            'postal_code' => Arr::get($data, 'postal_code'),
            'city' => Arr::get($data, 'city'),
            'state' => Arr::get($data, 'state'),
            'country_code' => Arr::get($data, 'country_code', 'FR'),
            'phone' => Arr::get($data, 'phone'),
            'email' => $email,
        ];
    }

    public function ensureAddresses(User $user, array $addresses, ?string $email = null): array
    {
        $sanitize = fn ($data) => [
            'first_name' => Arr::get($data, 'first_name'),
            'last_name' => Arr::get($data, 'last_name'),
            'company' => Arr::get($data, 'company'),
            'line1' => Arr::get($data, 'line1'),
            'line2' => Arr::get($data, 'line2'),
            'postal_code' => Arr::get($data, 'postal_code'),
            'city' => Arr::get($data, 'city'),
            'state' => Arr::get($data, 'state'),
            'country_code' => Arr::get($data, 'country_code', 'FR'),
            'phone' => Arr::get($data, 'phone'),
        ];

        $billing = Address::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'billing',
                'line1' => Arr::get($addresses, 'billing.line1'),
                'postal_code' => Arr::get($addresses, 'billing.postal_code'),
            ],
            array_merge($sanitize($addresses['billing']), [
                'user_id' => $user->id,
                'type' => 'billing',
                'is_default_billing' => true,
            ])
        );

        $shippingAddress = Address::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'shipping',
                'line1' => Arr::get($addresses, 'shipping.line1'),
                'postal_code' => Arr::get($addresses, 'shipping.postal_code'),
            ],
            array_merge($sanitize($addresses['shipping']), [
                'user_id' => $user->id,
                'type' => 'shipping',
                'is_default_shipping' => true,
            ])
        );

        $billingArray = $billing->toArrayForOrder();
        $shippingArray = $shippingAddress->toArrayForOrder();

        if ($email) {
            $billingArray['email'] = $email;
            $shippingArray['email'] = $email;
        }

        return [$billingArray, $shippingArray];
    }

    public function createOrder(Cart $cart, ?User $user, array $payload): array
    {
        return DB::transaction(function () use ($cart, $user, $payload): array {
            $customerEmail = $this->normalizeEmail(Arr::get($payload, 'customer_email') ?? $user?->email);
            $addresses = $payload['addresses'] ?? [];

            if ($user) {
                [$billing, $shippingAddress] = $this->ensureAddresses($user, $addresses, $customerEmail);
            } else {
                $billing = $this->sanitizeAddressForOrder(Arr::get($addresses, 'billing', []), $customerEmail);
                $shippingAddress = $this->sanitizeAddressForOrder(Arr::get($addresses, 'shipping', []), $customerEmail);
            }

            $shippingOption = $this->shipping->findOption($payload['shipping_method']);
            $shippingBasePrice = (float) Arr::get(is_array($shippingOption->metadata ?? null) ? $shippingOption->metadata : [], 'price_original', (float) ($shippingOption->price ?? 0));
            $forcedShipping = $cart->forcedShippingFee();
            $freeShippingMin = (float) (config('pricing.free_shipping_min_total') ?? 0);
            $shippingSurcharge = (float) (config('pricing.shipping_surcharge') ?? 0);
            // Eligibilité calculée sur le panier avant remise
            $eligibleBase = max(0, ($cart->subtotal_ttc ?? 0));
            $hasShippingPromo = $cart->promoCode && $cart->promoCode->discount_type === 'shipping';
            $isFreeShippingApplied = ! $forcedShipping
                && $shippingSurcharge <= 0
                && ($hasShippingPromo || ($freeShippingMin > 0 && $eligibleBase >= $freeShippingMin));
            if ($isFreeShippingApplied) {
                $shippingOption->price = 0;
            }
            if ($forcedShipping) {
                $shippingOption->price = (float) $forcedShipping + $shippingSurcharge;
                $shippingBasePrice = (float) $forcedShipping + $shippingSurcharge;
            }
            $cart->update(['shipping_total' => $shippingOption->price]);
            $totals = $this->pricing->calculateCartTotals($cart->fresh('items', 'promoCode'));
            $cart->fill($totals)->save();

            $metadata = Arr::get($payload, 'metadata', []);
            if (! $user && $customerEmail) {
                $metadata = array_merge($metadata, ['guest_email' => $customerEmail]);
            }

            $shippingMeta = is_array($shippingOption->metadata ?? null) ? $shippingOption->metadata : [];
            $shippingMeta = array_merge($shippingMeta, [
                'provider' => $shippingOption->provider ?? null,
                'type' => $shippingOption->type ?? null,
                'price' => (float) ($shippingOption->price ?? 0),
                'price_original' => $shippingBasePrice,
                'is_free' => ($isFreeShippingApplied && $shippingBasePrice > 0),
            ]);
            $metadata = array_merge($metadata, ['shipping' => $shippingMeta]);

            $order = Order::create([
                'user_id' => $user?->id,
                'cart_id' => $cart->id,
                'shipping_method_id' => $shippingOption->method_id,
                'promo_code_id' => $cart->promo_code_id,
                'number' => $this->generateOrderNumber(),
                'delivery_type' => $shippingOption->type,
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Pending,
                'subtotal_ht' => $cart->subtotal_ht,
                'subtotal_ttc' => $cart->subtotal_ttc,
                'tax_total' => $cart->tax_total,
                'discount_total' => $cart->discount_total,
                'shipping_total' => $cart->shipping_total,
                'total_ht' => $cart->subtotal_ht - $cart->discount_total + $cart->shipping_total,
                'total_ttc' => $cart->subtotal_ttc - $cart->discount_total + $cart->shipping_total,
                'billing_address' => $billing,
                'shipping_address' => $shippingAddress,
                'is_pro' => $user?->isPro() ?? false,
                'customer_note' => Arr::get($payload, 'notes'),
                'metadata' => $metadata,
                'placed_at' => now(),
            ]);

            $cart->items->each(function ($item) use ($order): void {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'name' => $item->name,
                    'reference' => $item->reference,
                    'variant_snapshot' => $item->variant_snapshot,
                    'quantity' => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                    'unit_price_ttc' => $item->unit_price_ttc,
                    'tax_rate' => $item->tax_rate,
                    'discount_total' => $item->discount_total,
                    'total_ht' => $item->unit_price_ht * $item->quantity,
                    'total_ttc' => $item->unit_price_ttc * $item->quantity,
                    'warranty_months' => $item->variant?->product?->warranty_months ?? 6,
                ]);
            });

            $order->load('items.variant');
            $this->stock->decrementForOrder($order);

            $paymentIntent = $this->payments->createIntent($order, $payload['payment'] ?? []);
            Payment::create([
                'order_id' => $order->id,
                'provider' => $paymentIntent['provider'],
                'method' => $paymentIntent['method'],
                'amount' => $order->total_ttc,
                'currency' => 'EUR',
                'status' => $paymentIntent['status'],
                'transaction_reference' => $paymentIntent['reference'],
                'payload' => $paymentIntent['payload'] ?? [],
            ]);

            $cart->update(['status' => 'converted']);

            return [
                'order' => $order->load('items', 'payments'),
                'payment_intent' => $paymentIntent,
            ];
        });
    }

    private function generateOrderNumber(): string
    {
        return 'RP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }
}
