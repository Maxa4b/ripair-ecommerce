@extends('layouts.app', ['title' => 'Mes commandes'])

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
            .orders-shell {
                padding: 0 12px;
                box-sizing: border-box;
            }
        }

        .orders-shell { display: grid; gap: 20px; }
        .orders-hero {
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
        .orders-hero h1 { margin: 0; font-size: 22px; font-weight: 800; color: #0b1f4f; }
        .orders-hero p { margin: 4px 0 0; color: #475569; font-size: 14px; }
        .orders-actions { display:flex; gap:10px; flex-wrap:wrap; }
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

        /* Layout cartes : bloc principal + bloc facture + bloc total/détails empilés */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .order-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            text-decoration: none;
            color: inherit;
            display: grid;
            grid-template-rows: auto auto;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        .order-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,167,255,0.08), rgba(29,91,255,0.04));
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        .order-card:hover::after { opacity: 1; }

        .order-info { display:grid; gap:4px; }
        .order-head {
            display:flex;
            align-items:center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .order-badge {
            background: #eaf8ff;
            color: #0b63f6;
            padding: 6px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 12px;
        }
        .order-status {
            padding: 6px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 12px;
            background: #f4f4f5;
            color: #0f172a;
        }
        .order-title { margin: 0; font-size: 16px; font-weight: 800; color: #0b1f4f; }
        .order-meta { margin: 0; color: #556177; font-size: 13px; }

        .order-actions {
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap: 12px;
            width: 100%;
        }
        .order-total { margin: 0; font-size: 18px; font-weight: 800; color: #0b1f4f; text-align:left; }
        .order-actions .account-link { padding-top:4px; }

        @media (max-width: 1024px) {
            .orders-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .orders-hero {
                padding: 18px;
            }
            .orders-actions {
                width: 100%;
            }
            .orders-actions .btn-primary {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .orders-hero h1 {
                font-size: 18px;
            }
            .order-card {
                padding: 14px;
            }
            .order-title {
                font-size: 14px;
            }
            .order-meta {
                font-size: 12px;
            }
            .order-total {
                font-size: 16px;
            }
        }

        @media (max-width: 540px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="orders-shell">
        <div class="orders-hero">
            <div>
                <h1>Mes commandes</h1>
                <p>Suivi, statuts et factures.</p>
            </div>
            <div class="orders-actions">
                <a class="btn-primary" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        @if(($hasDraft ?? false) && ($draftCart ?? null))
            @php
                $draftLabel = match((int) ($draftStep ?? 1)) {
                    4 => 'Paiement',
                    3 => 'Livraison',
                    2 => 'Adresses',
                    default => 'Identification',
                };
                $draftAmount = max(0, (float) ($draftCart->subtotal_ttc ?? 0) - (float) ($draftCart->discount_total ?? 0));
                $itemCount = (int) ($draftCart->items_count ?? 0);
                $itemLabel = $itemCount === 1 ? 'article' : 'articles';
            @endphp
            <div class="orders-hero" style="background:linear-gradient(135deg,#ffffff,#f0fbff);border-color:#dbeafe;">
                <div>
                    <h1 style="font-size:18px;">Commande en cours</h1>
                    <p>
                        Étape : <strong style="color:#0b1f4f;">{{ $draftLabel }}</strong>
                        &middot; {{ $itemCount }} {{ $itemLabel }}
                        &middot; {{ number_format($draftAmount, 2, ',', ' ') }} &euro;
                    </p>
                </div>
                <div class="orders-actions">
                    <a class="btn-primary" href="{{ route('checkout.index', ['resume' => 1]) }}">Reprendre</a>
                </div>
            </div>
        @endif

        <div class="orders-grid">
            @foreach ($orders as $order)
                @php
                    $deliveryLabel = match($order->delivery_type?->value ?? $order->delivery_type ?? '') {
                        'relay' => 'Point relais',
                        'workshop_pickup' => 'Retrait atelier',
                        'shipping', '' => 'Livraison',
                        default => ucfirst((string) $order->delivery_type),
                    };
                    $invoice = $order->invoice;
                @endphp
                <a class="order-card" href="{{ route('account.commandes.show', $order) }}">
                    <div class="order-info">
                        <div class="order-head">
                            <span class="order-badge">{{ $order->number }}</span>
                            <span class="order-status">{{ $order->status->label() }}</span>
                        </div>
                        <p class="order-title">{{ $order->placed_at?->format('d/m/Y') }}</p>
                        <p class="order-meta">Paiement : {{ $order->payment_status->label() }}</p>
                        <p class="order-meta">Livraison : {{ $deliveryLabel }}</p>
                    </div>
                    <div class="order-actions">
                        <p class="order-total">{{ number_format($order->total_ttc, 2, ',', ' ') }} €</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
