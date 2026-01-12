<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Commerce\Invoice;
use Illuminate\Http\Request;
use App\Services\Documents\InvoicePdfService;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('issued_at')
            ->paginate(15);

        return view('account.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        abort_unless($invoice->order->user_id === auth()->id(), 403);

        return view('account.invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice, InvoicePdfService $pdfService)
    {
        abort_unless($invoice->order->user_id === auth()->id(), 403);

        $path = $pdfService->generate($invoice);

        return response()->download($path, "{$invoice->number}.pdf")->deleteFileAfterSend(true);
    }
}
