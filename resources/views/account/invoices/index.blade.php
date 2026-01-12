@extends('layouts.app', ['title' => 'Factures'])

@section('content')
    <style>
        .invoices-shell { display: grid; gap: 20px; }
        .invoices-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5edf7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .invoices-hero h1 { margin: 0; font-size: 22px; font-weight: 800; color: #0b1f4f; }
        .invoices-hero p { margin: 4px 0 0; color: #475569; font-size: 14px; }
        .invoice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }
        .invoices-actions { display:flex; gap:10px; flex-wrap:wrap; }
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
        .invoice-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: grid;
            gap: 8px;
        }
        .invoice-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,167,255,0.08), rgba(29,91,255,0.04));
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        .invoice-card:hover::after { opacity: 1; }
        .invoice-head { display:flex;justify-content:space-between;align-items:center;gap:8px; }
        .invoice-badge {
            padding: 6px 10px;
            border-radius: 12px;
            background: #eaf8ff;
            color: #0b63f6;
            font-weight: 700;
            font-size: 12px;
        }
        .invoice-date { color:#556177;font-size:13px;margin:0; }
        .invoice-total { margin:0; font-weight:800; color:#0b1f4f; font-size:18px; }
    </style>

    <div class="invoices-shell">
        <div class="invoices-hero">
            <div>
                <h1>Mes factures</h1>
                <p>Reçus, téléchargements et archives.</p>
            </div>
            <div class="invoices-actions">
                <a class="btn-primary" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        <div class="invoice-grid">
            @foreach ($invoices as $invoice)
                <a class="invoice-card" href="{{ route('account.factures.show', $invoice) }}">
                    <div class="invoice-head">
                        <span class="invoice-badge">{{ $invoice->number }}</span>
                        <span class="invoice-date">{{ $invoice->issued_at?->format('d/m/Y') }}</span>
                    </div>
                    <p class="invoice-total">{{ number_format($invoice->amount_ttc, 2, ',', ' ') }} €</p>
                    <p class="invoice-date">Commande : {{ $invoice->order->number ?? '—' }}</p>
                    <span class="account-link" style="padding-top:4px;">Voir / Télécharger</span>
                </a>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
