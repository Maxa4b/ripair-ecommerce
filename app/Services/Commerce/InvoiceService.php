<?php

namespace App\Services\Commerce;

use App\Models\Commerce\Invoice;
use App\Models\Commerce\Order;
use App\Services\Documents\InvoicePdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        protected InvoicePdfService $pdfs = new InvoicePdfService(),
    ) {
    }

    public function generate(Order $order): Invoice
    {
        $number = $order->invoice_number ?? $this->nextInvoiceNumber();

        $invoice = Invoice::updateOrCreate(
            ['order_id' => $order->id],
            [
                'number' => $number,
                'amount_ht' => $order->total_ht,
                'amount_ttc' => $order->total_ttc,
                'tax_total' => $order->tax_total,
                'metadata' => [
                    'customer' => $order->billing_address,
                    'shipping' => $order->shipping_address,
                ],
            ],
        );

        $order->update(['invoice_number' => $number]);

        return $invoice;
    }

    /**
     * Envoie la facture par e-mail au client (compte ou invité) si possible.
     * Evite les doublons via metadata.invoice_sent.
     */
    public function sendToCustomer(Invoice $invoice): void
    {
        $order = $invoice->order;
        if (! $order) {
            return;
        }

        $metadata = $order->metadata ?? [];
        if (data_get($metadata, 'invoice_sent')) {
            return;
        }

        $email = $order->user?->email ?? data_get($order->billing_address, 'email');
        if (! $email) {
            return;
        }

        try {
            $pdfPath = $this->pdfs->generate($invoice);
        } catch (\Throwable $e) {
            Log::warning('invoice_pdf_failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
            return;
        }

        $subject = 'Votre facture '.$invoice->number.' – commande '.$order->number;
        $total = number_format($invoice->amount_ttc ?? $order->total_ttc, 2, ',', ' ');
        $html = view('emails.order_confirmation', [
            'order' => $order,
            'total' => $total,
            'statusLabel' => $order->status?->label() ?? 'Payée',
        ])->render();
        $text = "Merci pour votre commande {$order->number}.\nMontant TTC : {$total} €\n\nVotre facture est jointe à cet e-mail.";

        try {
            Mail::send([], [], function ($mail) use ($email, $subject, $html, $text, $pdfPath, $invoice) {
                $mail->to($email)->subject($subject);
                $mail->html($html);
                $mail->text($text);
                $mail->attach($pdfPath, [
                    'as' => $invoice->number.'.pdf',
                    'mime' => 'application/pdf',
                ]);
            });

            $order->update(['metadata' => array_merge($metadata, [
                'invoice_sent' => true,
                'confirmation_sent' => true, // évite un second mail de confirmation séparé
            ])]);
            // Rafraîchit l'instance pour les appels qui suivent
            $order->refresh();
        } catch (\Throwable $e) {
            Log::warning('invoice_mail_failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Y');

        $last = Invoice::query()
            ->where('number', 'like', "{$prefix}%")
            ->max('number');

        $increment = $last ? ((int) Str::afterLast($last, '-') + 1) : 1;
        $number = "{$prefix}-" . str_pad((string) $increment, 5, '0', STR_PAD_LEFT);

        // Sécurise contre un numéro déjà présent (tests répétés)
        while (Invoice::where('number', $number)->exists()) {
            $increment++;
            $number = "{$prefix}-" . str_pad((string) $increment, 5, '0', STR_PAD_LEFT);
        }

        return $number;
    }
}
