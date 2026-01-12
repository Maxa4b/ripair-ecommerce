<?php

namespace App\Services\Payments;

use App\Models\Commerce\Order;

class PayPalService
{
    public function enabled(): bool
    {
        return ! empty(config('services.paypal.client_id')) && ! empty(config('services.paypal.secret'));
    }

    public function createOrder(Order $order): array
    {
        // Si SDK absent, on renvoie un stub pour éviter un crash
        if (! class_exists(\PayPalCheckoutSdk\Orders\OrdersCreateRequest::class)) {
            return [
                'status' => 'pending',
                'reference' => null,
                'approve_url' => null,
                'error' => 'SDK PayPal manquant (extensions PHP ou installation Composer incomplète)',
            ];
        }

        $client = $this->client();

        $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'custom_id' => (string) $order->id,
                'invoice_id' => $order->number,
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($order->total_ttc, 2, '.', ''),
                ],
                'description' => 'Commande '.$order->number,
            ]],
            'application_context' => [
                'brand_name' => config('app.name', 'RIPAIR'),
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'return_url' => config('services.paypal.return_url') ?? route('checkout.paypal.success'),
                'cancel_url' => config('services.paypal.cancel_url') ?? route('checkout.paypal.cancel'),
            ],
        ];

        try {
            $response = $client->execute($request);
            $approve = collect($response->result->links ?? [])->firstWhere('rel', 'approve');

            return [
                'status' => 'pending',
                'reference' => $response->result->id ?? null,
                'approve_url' => $approve->href ?? null,
                'raw' => $response->result ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'pending',
                'reference' => null,
                'approve_url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function capture(string $paypalOrderId): array
    {
        if (! class_exists(\PayPalCheckoutSdk\Orders\OrdersCaptureRequest::class)) {
            return ['status' => 'failed', 'error' => 'SDK PayPal manquant'];
        }

        $client = $this->client();
        $request = new \PayPalCheckoutSdk\Orders\OrdersCaptureRequest($paypalOrderId);
        $request->prefer('return=representation');

        try {
            $response = $client->execute($request);
            $status = strtolower($response->result->status ?? 'PAYER_ACTION_REQUIRED');
            $isPaid = in_array($status, ['completed', 'approved']);
            $captureId = data_get($response->result, 'purchase_units.0.payments.captures.0.id');

            return [
                'status' => $isPaid ? 'paid' : 'pending',
                'reference' => $paypalOrderId,
                'capture_id' => $captureId,
                'raw' => $response->result ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'reference' => $paypalOrderId,
            ];
        }
    }

    private function client()
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');
        $mode = config('services.paypal.mode', 'live');

        $environment = $mode === 'sandbox'
            ? new \PayPalCheckoutSdk\Core\SandboxEnvironment($clientId, $secret)
            : new \PayPalCheckoutSdk\Core\ProductionEnvironment($clientId, $secret);

        return new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
    }
}
