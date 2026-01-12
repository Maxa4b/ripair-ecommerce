@extends('layouts.app', ['title' => "Facture {$invoice->number}"])

@section('content')
    <style>
        .invoice-shell { display:grid; gap:16px; }
        .invoice-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5edf7;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }
        .invoice-hero h1 { margin:0; font-size:20px; font-weight:800; color:#0b1f4f; }
        .invoice-hero p { margin:0; color:#475569; font-size:14px; }
        .invoice-cards {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(240px,1fr));
            gap:12px;
        }
        .invoice-card {
            background:#fff;
            border:1px solid #e7edf5;
            border-radius:14px;
            padding:14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
        }
        .invoice-card h3 { margin:0 0 6px; font-size:15px; font-weight:800; color:#0b1f4f; }
        .invoice-card p { margin:0; color:#556177; font-size:14px; }
        .items-block {
            background:#fff;
            border:1px solid #e7edf5;
            border-radius:16px;
            padding:16px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .items-header { display:flex; justify-content:space-between; align-items:center; }
        .items-header h2 { margin:0; font-size:17px; font-weight:800; color:#0b1f4f; }
        .pill {
            display:inline-flex;
            align-items:center;
            padding:6px 10px;
            border-radius:12px;
            font-weight:700;
            font-size:12px;
            background:#eaf8ff;
            color:#0b63f6;
        }
        .item-row {
            display:grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items:center;
            padding:10px 0;
            border-bottom:1px solid #eef2f7;
        }
        .item-row:last-child { border-bottom:none; }
        .item-name { margin:0; font-weight:700; color:#0f172a; }
        .item-meta { margin:0; color:#6b7280; font-size:13px; }
        .total-block { display:flex; justify-content:flex-end; gap:20px; flex-wrap:wrap; margin-top:12px; color:#0f172a; }
        .total-line { display:flex; justify-content:space-between; width:220px; font-size:14px; }
        .total-strong { font-weight:800; font-size:16px; }
        .invoice-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn-primary {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: var(--blue, #3abafc);
            color: #fff;
            border: none;
            border-radius: 28px;
            padding: 12px 26px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(58,186,252,.25);
            transition: filter .2s, transform .2s;
        }
        .btn-primary:hover { filter:brightness(.92); transform: translateY(-1px); }
        .account-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #3abafc;
            font-weight: 700;
            text-decoration: none;
        }
    </style>

    <div class="invoice-shell">
        <div class="invoice-hero">
            <div>
                <h1>Facture {{ $invoice->number }}</h1>
                <p>Émise le {{ $invoice->issued_at?->format('d/m/Y') }}</p>
            </div>
            <div class="invoice-actions">
                <a class="btn-primary" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        <div class="invoice-cards">
            <div class="invoice-card">
                <h3>Montants</h3>
                <p>Montant HT : {{ number_format($invoice->amount_ht, 2, ',', ' ') }} €</p>
                <p>Montant TTC : {{ number_format($invoice->amount_ttc, 2, ',', ' ') }} €</p>
                <p>Taxes : {{ number_format($invoice->tax_total ?? ($invoice->amount_ttc - $invoice->amount_ht), 2, ',', ' ') }} €</p>
            </div>
            <div class="invoice-card">
                <h3>Commande liée</h3>
                <p>
                    Commande :
                    @if($invoice->order)
                        <a class="account-link" href="{{ route('account.commandes.show', $invoice->order) }}">{{ $invoice->order->number }}</a>
                    @else
                        —
                    @endif
                </p>
                <p>Date : {{ $invoice->order?->placed_at?->format('d/m/Y') ?? '—' }}</p>
                <p>Statut : {{ $invoice->order?->status?->label() ?? '—' }}</p>
            </div>
            <div class="invoice-card">
                <h3>Téléchargement</h3>
                <p>Recevez le PDF de votre facture.</p>
                <a href="{{ route('account.factures.download', $invoice) }}" class="btn-primary" style="margin-top:6px;display:inline-block;">Télécharger le PDF</a>
            </div>
        </div>

        <div class="items-block">
            <div class="items-header">
                <h2>Détail commande</h2>
                <span class="pill">{{ $invoice->order?->items?->count() ?? 0 }} article(s)</span>
            </div>
            @foreach ($invoice->order->items ?? [] as $item)
                <div class="item-row">
                    <div>
                        <p class="item-name">{{ $item->name }}</p>
                        <p class="item-meta">Réf : {{ $item->reference ?? '—' }}</p>
                    </div>
                    <div class="item-name" style="text-align:right;">{{ number_format($item->total_ttc, 2, ',', ' ') }} €</div>
                </div>
            @endforeach
            <div class="total-block">
                <div class="total-line"><span>Sous-total TTC</span><span>{{ number_format($invoice->order->subtotal_ttc ?? $invoice->amount_ttc, 2, ',', ' ') }} €</span></div>
                <div class="total-line"><span>Livraison</span><span>{{ number_format($invoice->order->shipping_total ?? 0, 2, ',', ' ') }} €</span></div>
                <div class="total-line total-strong"><span>Total TTC</span><span>{{ number_format($invoice->amount_ttc, 2, ',', ' ') }} €</span></div>
            </div>
        </div>
    </div>
@endsection
