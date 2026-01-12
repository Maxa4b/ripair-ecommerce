@extends('layouts.app', ['title' => "Commande {$order->number}"])

@section('content')
    <style>
        @media (max-width: 1024px) {
            body {
                background: linear-gradient(180deg, #f5f7fb 0%, #eef2ff 40%, #ffffff 100%);
                overflow-x: hidden;
            }
            main.container {
                padding: 18px 0 32px !important;
            }
            .order-shell {
                padding: 0 12px;
                box-sizing: border-box;
            }
        }

        .order-shell { display: grid; gap: 18px; }
        .order-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5edf7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .order-hero h1 { margin:0; font-size:20px; font-weight:800; color:#0b1f4f; }
        .order-hero p { margin:0; color:#475569; font-size:14px; }
        .order-actions { display:flex; gap:10px; flex-wrap:wrap; }
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
        .order-summary {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(240px,1fr));
            gap: 12px;
        }
        .info-card {
            background:#fff;
            border:1px solid #e7edf5;
            border-radius:14px;
            padding:14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
        }
        .info-card h3 { margin:0 0 6px; font-size:15px; font-weight:800; color:#0b1f4f; }
        .info-card p { margin:0; color:#556177; font-size:14px; overflow-wrap:anywhere; }
        .items-block {
            background:#fff;
            border:1px solid #e7edf5;
            border-radius:16px;
            padding:16px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .items-header { display:flex; justify-content:space-between; align-items:center; }
        .items-header h2 { margin:0; font-size:17px; font-weight:800; color:#0b1f4f; }
        .item-row {
            display:grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            align-items:center;
            padding:10px 0;
            border-bottom:1px solid #eef2f7;
        }
        .item-row:last-child { border-bottom:none; }
        .item-name { margin:0; font-weight:700; color:#0f172a; }
        .item-meta { margin:0; color:#6b7280; font-size:13px; }
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
        .total-block {
            display:flex;
            justify-content:flex-end;
            gap: 20px;
            flex-wrap:wrap;
            margin-top:12px;
            color:#0f172a;
        }
        .total-line { display:flex; justify-content:space-between; width:220px; font-size:14px; }
        .total-strong { font-weight:800; font-size:16px; }

        @media (max-width: 768px) {
            .order-hero {
                padding: 18px;
            }
            .order-actions {
                width: 100%;
            }
            .order-actions .btn-primary {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .order-summary {
                grid-template-columns: 1fr;
            }
            .items-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .item-row {
                grid-template-columns: 1fr auto;
                grid-template-areas:
                    "name name"
                    "qty price";
                align-items: start;
                gap: 8px;
            }
            .item-row > div:nth-child(1) {
                grid-area: name;
            }
            .item-row > div:nth-child(2) {
                grid-area: qty;
                justify-self: start;
            }
            .item-row > div:nth-child(3) {
                grid-area: price;
                justify-self: end;
            }
            .item-name {
                overflow-wrap: anywhere;
                font-size: 14px;
            }
            .item-meta {
                font-size: 12px;
            }
        }
    </style>

    <div class="order-shell">
        <div class="order-hero">
            <div>
                <h1>Commande {{ $order->number }}</h1>
                <p>{{ $order->placed_at?->format('d/m/Y H:i') }} · {{ $order->status->label() }}</p>
            </div>
            <div class="order-actions">
                <a class="btn-primary" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        @php
            $deliveryLabel = match($order->delivery_type?->value ?? $order->delivery_type ?? '') {
                'relay' => 'Point relais',
                'workshop_pickup' => 'Retrait atelier',
                'shipping', '' => 'Livraison',
                default => ucfirst((string) $order->delivery_type),
            };
        @endphp
        <div class="order-summary">
            <div class="info-card">
                <h3>Livraison</h3>
                <p>{{ $order->shipping_address['first_name'] ?? '' }} {{ $order->shipping_address['last_name'] ?? '' }}</p>
                <p>{{ $order->shipping_address['line1'] ?? '' }}</p>
                <p>{{ $order->shipping_address['postal_code'] ?? '' }} {{ $order->shipping_address['city'] ?? '' }} · {{ strtoupper($order->shipping_address['country_code'] ?? '') }}</p>
                @if(data_get($order, 'shipping_address.phone'))
                    <p>Tél : {{ data_get($order, 'shipping_address.phone') }}</p>
                @endif
                <p>Transporteur : {{ $order->shippingMethod?->name ?? $order->carrier_name ?? '—' }}</p>
                @php
                    $trackingNumber = trim((string) ($order->tracking_number ?? ''));
                    $operator = strtoupper((string) data_get($order->metadata, 'shipping_label.operator', ''));
                    $carrierHint = strtoupper((string) ($order->carrier_name ?? $order->shippingMethod?->name ?? ''));

                    if ($operator === '') {
                        if (str_contains($carrierHint, 'CHRONO')) {
                            $operator = 'CHRP';
                        } elseif (str_contains($carrierHint, 'MONDIAL')) {
                            $operator = 'MONR';
                        } elseif (str_contains($carrierHint, 'COLISSIMO') || str_contains($carrierHint, 'LA POSTE') || str_contains($carrierHint, 'POSTE')) {
                            $operator = 'POFR';
                        }
                    }

                    $trackingUrl = null;
                    if ($trackingNumber !== '') {
                        $q = urlencode($trackingNumber);
                        $trackingUrl = match ($operator) {
                            'POFR', 'COLI' => "https://www.laposte.fr/outils/suivre-vos-envois?code={$q}",
                            'CHRP' => "https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT={$q}",
                            'MONR' => "https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition={$q}",
                            default => null,
                        };
                    }
                @endphp
                <div style="margin-top:8px;padding:10px;border:1px dashed #dfe5ef;border-radius:12px;background:#f8fbff;color:#475569;font-size:14px;">
                    <strong>Numéro de suivi</strong><br>
                    @if($trackingNumber === '')
                        <span>À venir dès l’expédition.</span>
                    @elseif($trackingUrl)
                        <a href="{{ $trackingUrl }}" target="_blank" rel="noreferrer noopener" style="color:#3abafc;font-weight:800;text-decoration:underline;">
                            {{ $trackingNumber }}
                        </a>
                    @else
                        <span>{{ $trackingNumber }}</span>
                    @endif
                </div>
            </div>
            <div class="info-card">
                <h3>Facturation</h3>
                <p>{{ $order->billing_address['first_name'] ?? '' }} {{ $order->billing_address['last_name'] ?? '' }}</p>
                <p>{{ $order->billing_address['line1'] ?? '' }}</p>
                <p>{{ $order->billing_address['postal_code'] ?? '' }} {{ $order->billing_address['city'] ?? '' }} · {{ strtoupper($order->billing_address['country_code'] ?? '') }}</p>
                @if(data_get($order, 'billing_address.phone'))
                    <p>Tél : {{ data_get($order, 'billing_address.phone') }}</p>
                @endif
            </div>
            <div class="info-card">
                <h3>Totaux</h3>
                <p>Sous-total TTC : {{ number_format($order->subtotal_ttc ?? $order->total_ttc, 2, ',', ' ') }} €</p>
                <p>Livraison : {{ number_format($order->shipping_total ?? 0, 2, ',', ' ') }} €</p>
                <p class="total-strong">Total TTC : {{ number_format($order->total_ttc, 2, ',', ' ') }} €</p>
            </div>
        </div>

        <div class="order-summary">
            <div class="info-card">
                <h3>Facture</h3>
                @if($order->invoice)
                    <p>Numéro : <a href="{{ route('account.factures.show', $order->invoice) }}" class="account-link" style="font-weight:800;color:#3abafc;text-decoration:none;">{{ $order->invoice->number }}</a></p>
                    <p>Montant TTC : {{ number_format($order->invoice->amount_ttc ?? $order->total_ttc, 2, ',', ' ') }} €</p>
                    <a class="btn" style="margin-top:6px;display:inline-block;" href="{{ route('account.factures.show', $order->invoice) }}">Voir la facture</a>
                @else
                    <p>Aucune facture générée pour cette commande.</p>
                @endif
            </div>
        </div>

        <div class="items-block">
            <div class="items-header">
                <h2>Articles</h2>
                <span class="pill">{{ $order->items->count() }} article(s)</span>
            </div>
            @foreach ($order->items as $item)
                <div class="item-row">
                    <div>
                        <p class="item-name">{{ $item->name }}</p>
                        <p class="item-meta">Réf : {{ $item->reference ?? '—' }}</p>
                    </div>
                    <div class="item-meta">x{{ $item->quantity }}</div>
                    <div class="item-name" style="text-align:right;">{{ number_format($item->total_ttc, 2, ',', ' ') }} €</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
