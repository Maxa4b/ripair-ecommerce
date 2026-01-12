<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Commerce\Payment;
use App\Services\Commerce\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, InvoiceService $invoices)
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            return response('Webhook secret missing', 400);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        $type = $event->type ?? '';

        if ($type === 'checkout.session.completed') {
            $session = $event->data->object;
            $sessionId = $session->id ?? null;
            $paymentIntentId = $session->payment_intent ?? null;

            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->whereIn('transaction_reference', array_filter([$sessionId, $paymentIntentId]))
                ->latest()
                ->first();

            if (! $payment || ! $payment->order) {
                Log::warning('stripe_webhook_payment_not_found', ['session_id' => $sessionId, 'payment_intent' => $paymentIntentId]);
                return response()->json(['status' => 'ignored']);
            }

            $order = $payment->order;
            if ($order->payment_status !== PaymentStatus::Paid) {
                $payment->update([
                    'status' => PaymentStatus::Paid->value,
                    'transaction_reference' => $paymentIntentId ?: $payment->transaction_reference,
                    'payload' => array_merge($payment->payload ?? [], ['checkout_session' => $session]),
                ]);

                $order->update([
                    'payment_status' => PaymentStatus::Paid,
                    'status' => OrderStatus::Paid,
                ]);

                $invoice = $invoices->generate($order);
                $invoices->sendToCustomer($invoice);
                $order->refresh();
                $this->sendConfirmationEmail($order);
            }

            return response()->json(['status' => 'ok']);
        }

        if ($type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $intentId = $intent->id ?? null;

            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->where('transaction_reference', $intentId)
                ->latest()
                ->first();

            if ($payment && $payment->order && $payment->order->payment_status !== PaymentStatus::Paid) {
                $order = $payment->order;
                $payment->update([
                    'status' => PaymentStatus::Paid->value,
                    'payload' => array_merge($payment->payload ?? [], ['payment_intent' => $intent]),
                ]);
                $order->update([
                    'payment_status' => PaymentStatus::Paid,
                    'status' => OrderStatus::Paid,
                ]);
                $invoice = $invoices->generate($order);
                $invoices->sendToCustomer($invoice);
                $order->refresh();
                $this->sendConfirmationEmail($order);
            }

            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'ignored']);
    }

    private function sendConfirmationEmail($order): void
    {
        $metadata = $order->metadata ?? [];
        if (data_get($metadata, 'confirmation_sent') || data_get($metadata, 'invoice_sent')) {
            return;
        }

        $to = $order->user?->email ?? data_get($order->billing_address, 'email');
        if (! $to) {
            return;
        }

        $subject = 'Confirmation de commande '.$order->number;
        $total = number_format($order->total_ttc, 2, ',', ' ');
        $statusLabel = $order->status?->label() ?? 'Payée';

        $html = view('emails.order_confirmation', [
            'order' => $order,
            'total' => $total,
            'statusLabel' => $statusLabel,
        ])->render();

        $text = "Merci pour votre commande {$order->number}.\nMontant TTC : {$total} €\nStatut : {$statusLabel}\n\nNous préparons votre commande et vous informerons dès l’expédition.";

        try {
            Mail::send([], [], function ($mail) use ($to, $subject, $html, $text) {
                $mail->to($to)->subject($subject);
                $mail->html($html);
                $mail->text($text);
            });

            $order->update(['metadata' => array_merge($metadata, ['confirmation_sent' => true])]);
        } catch (\Throwable $e) {
            Log::warning('email_confirmation_failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
