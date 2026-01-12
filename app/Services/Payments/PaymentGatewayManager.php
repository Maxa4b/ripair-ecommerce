<?php

namespace App\Services\Payments;

use App\Models\Commerce\Order;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use App\Services\Payments\PayPalService;

class PaymentGatewayManager
{
    public function createIntent(Order $order, array $payload = []): array
    {
        $provider = $payload['provider'] ?? 'stripe';
        $method = $payload['method'] ?? 'card';

        if ($provider === 'stripe' && $method === 'card' && config('services.stripe.secret')) {
            if (! class_exists(\Stripe\StripeClient::class)) {
                $stripeInit = base_path('vendor/stripe/stripe-php/init.php');
                if (file_exists($stripeInit)) {
                    require_once $stripeInit;
                }
            }
            if (class_exists(\Stripe\StripeClient::class)) {
                return $this->createStripeCheckoutSession($order);
            }
            // Si Stripe n'est pas disponible (extensions manquantes), fallback stub
            return [
                'provider' => 'stripe',
                'method' => 'card',
                'status' => 'pending',
                'reference' => Str::uuid()->toString(),
                'payload' => [
                    'error' => 'Stripe PHP non disponible (extensions manquantes)',
                    'return_url' => route('checkout.confirmation', $order),
                ],
            ];
        }

        if ($provider === 'paypal' && $method === 'paypal') {
            $paypal = new PayPalService();
            if ($paypal->enabled()) {
                $intent = $paypal->createOrder($order);
                return [
                    'provider' => 'paypal',
                    'method' => 'paypal',
                    'status' => 'pending',
                    'reference' => $intent['reference'] ?? null,
                    'payload' => [
                        'approve_url' => $intent['approve_url'] ?? null,
                        'error' => $intent['error'] ?? null,
                    ],
                ];
            }
            return [
                'provider' => 'paypal',
                'method' => 'paypal',
                'status' => 'pending',
                'reference' => Str::uuid()->toString(),
                'payload' => [
                    'error' => 'PayPal non configuré (client_id/secret manquants)',
                    'return_url' => route('checkout.confirmation', $order),
                ],
            ];
        }

        // PayPal ou virement non implémentés ici : stub minimal
        return [
            'provider' => $provider,
            'method' => $method,
            'status' => 'pending',
            'reference' => Str::uuid()->toString(),
            'payload' => [
                'return_url' => route('checkout.confirmation', $order),
            ],
        ];
    }

    public function capture(Order $order, string $transactionReference): array
    {
        return [
            'reference' => $transactionReference,
            'status' => 'captured',
            'captured_at' => now(),
        ];
    }

    private function createStripeCheckoutSession(Order $order): array
    {
        $secret = config('services.stripe.secret');
        $public = config('services.stripe.key');
        $stripe = new StripeClient($secret);
        $amountCents = (int) round($order->total_ttc * 100);
        $successUrl = route('checkout.confirmation', $order) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('checkout.index');
        $email = data_get($order->billing_address, 'email') ?? $order->user?->email;

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $email,
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $amountCents,
                        'product_data' => [
                            'name' => 'Commande '.$order->number,
                            'description' => 'Paiement RIPAIR',
                        ],
                    ],
                ],
            ],
            'invoice_creation' => ['enabled' => false],
            'payment_method_types' => ['card'],
            'automatic_tax' => ['enabled' => false],
        ]);

        return [
            'provider' => 'stripe',
            'method' => 'card',
            'status' => 'pending',
            'reference' => $session->id,
            'payload' => [
                'checkout_url' => $session->url,
                'public_key' => $public,
            ],
        ];
    }
}
