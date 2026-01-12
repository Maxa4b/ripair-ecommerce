<?php

namespace App\Services\Documents;

use App\Models\Commerce\Invoice;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Génère un PDF de facture basique (une page) sans librairie externe.
     * Retourne le chemin absolu du fichier temporaire.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['order.items']);
        $order = $invoice->order;

        $customer = $invoice->metadata['customer'] ?? $order?->billing_address ?? [];
        $clientName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        if ($clientName === '') {
            $clientName = $order?->user?->name ?? $order?->user?->email ?? 'Client RIPAIR';
        }

        $issueDate = $invoice->issued_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $items = [];
        $linesTotal = 0.0;

        foreach ($order?->items ?? [] as $item) {
            $qty  = (int)($item->quantity ?? 1);
            $unit = (float)($item->unit_price_ttc ?? $item->unit_price_ht ?? 0);
            $line = (float)($item->total_ttc ?? $unit * $qty);
            $tax  = is_numeric($item->tax_rate ?? null) ? (float)$item->tax_rate : 0;

            $items[] = [
                'desc'  => trim($item->name ?? 'Article'),
                'qty'   => $qty,
                'unit'  => $unit,
                'tax'   => $tax,
                'total' => $line,
            ];
            $linesTotal += $line;
        }

        // Ligne de livraison éventuelle
        $shipping = (float)($order?->shipping_total ?? 0);
        if ($shipping > 0) {
            $items[] = [
                'desc'  => 'Livraison',
                'qty'   => 1,
                'unit'  => $shipping,
                'tax'   => 0,
                'total' => $shipping,
            ];
            $linesTotal += $shipping;
        }

        $subtotalHt = (float)($invoice->amount_ht ?? $order?->total_ht ?? $linesTotal);
        $taxTotal   = (float)($invoice->tax_total ?? $order?->tax_total ?? 0);
        $totalTtc   = (float)($invoice->amount_ttc ?? $order?->total_ttc ?? ($subtotalHt + $taxTotal));
        $discount   = (float)($order?->discount_total ?? 0);

        // Build PDF manually (WinAnsiEncoding to keep accents)
        $objects = [];
        $buffer = "%PDF-1.4\n";

        $addObject = function ($content) use (&$objects) {
            $objects[] = $content;
            return count($objects);
        };

        $fontRegular = $addObject("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>");
        $fontBold    = $addObject("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>");

        $content = '';
        $write = function ($x, $y, $text, $size = 10, $bold = false) use (&$content) {
            $fontId = $bold ? 2 : 1;
            $converted = @iconv('UTF-8', 'Windows-1252//IGNORE', $text);
            if ($converted === false) {
                $converted = $text;
            }
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
            $content .= sprintf("BT /F%d %d Tf %.2f %.2f Td (%s) Tj ET\n", $fontId, $size, $x, $y, $escaped);
        };

        $y = 800;
        $write(40, $y, 'RIPAIR', 20, true);
        $write(40, $y - 18, 'RÉPARATION ÉLECTRONIQUE', 10);
        $write(400, $y, 'contact@ripair.shop', 9);
        $write(400, $y - 14, '06 15 58 87 82', 9);

        $write(40, $y - 40, 'Facture n° ' . $invoice->number, 12, true);
        $write(40, $y - 54, 'Émise le : ' . $issueDate, 9);
        $write(40, $y - 68, 'Client : ' . $clientName, 9);

        $content .= "0 0 0 rg 1 w 40 " . ($y - 82) . " 515 0.5 re S\n";
        $headerY = $y - 98;
        $write(40, $headerY, 'Description', 10, true);
        $write(300, $headerY, 'Qté', 10, true);
        $write(340, $headerY, 'PU TTC', 10, true);
        $write(410, $headerY, 'TVA', 10, true);
        $write(470, $headerY, 'Montant', 10, true);

        $currentY = $headerY - 14;
        foreach ($items as $it) {
            $write(40, $currentY, $it['desc'], 9);
            $write(300, $currentY, (string)$it['qty'], 9);
            $write(340, $currentY, number_format($it['unit'], 2, ',', ' ') . ' €', 9);
            $write(410, $currentY, number_format($it['tax'], 2, ',', ' ') . ' %', 9);
            $write(470, $currentY, number_format($it['total'], 2, ',', ' ') . ' €', 9);
            $currentY -= 14;
        }

        if ($discount > 0) {
            $write(340, $currentY, 'Remise', 9, true);
            $write(470, $currentY, '-' . number_format($discount, 2, ',', ' ') . ' €', 9);
            $currentY -= 14;
        }

        $write(340, $currentY, 'Sous-total HT', 9, true);
        $write(470, $currentY, number_format($subtotalHt, 2, ',', ' ') . ' €', 9);
        $currentY -= 14;

        $write(340, $currentY, 'TVA', 9, true);
        $write(470, $currentY, number_format($taxTotal, 2, ',', ' ') . ' €', 9);
        $currentY -= 14;

        $write(340, $currentY, 'Total TTC', 11, true);
        $write(470, $currentY, number_format($totalTtc, 2, ',', ' ') . ' €', 11, true);

        $stream = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $contentsObj = $addObject($stream);

        $pageObj = $addObject("<< /Type /Page /Parent 3 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontRegular} 0 R /F2 {$fontBold} 0 R >> >> /Contents {$contentsObj} 0 R >>");
        $pagesObj = $addObject("<< /Type /Pages /Kids [{$pageObj} 0 R] /Count 1 >>");
        $catalogObj = $addObject("<< /Type /Catalog /Pages {$pagesObj} 0 R >>");

        $offset = strlen($buffer);
        $xref = "0 1\n0000000000 65535 f \n";
        foreach ($objects as $i => $obj) {
            $xref .= str_pad($offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
            $buffer .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
            $offset = strlen($buffer);
        }
        $xrefOffset = strlen($buffer);
        $buffer .= "xref\n" . $xref;
        $buffer .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogObj} 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        $tmpPath = 'tmp/invoice_' . $invoice->number . '_' . uniqid() . '.pdf';
        Storage::disk('local')->put($tmpPath, $buffer);

        return Storage::disk('local')->path($tmpPath);
    }
}
