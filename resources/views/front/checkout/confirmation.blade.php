@extends('layouts.app', ['title' => 'Confirmation'])

@section('content')
    <style>
        .confirm-shell { display:grid; gap:18px; }
        .confirm-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5edf7;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            flex-wrap:wrap;
        }
        .confirm-hero h1 { margin:0; font-size:22px; font-weight:800; color:#0b1f4f; }
        .confirm-hero p { margin:4px 0 0; color:#475569; font-size:14px; }
        .confirm-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn-primary {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: #3abafc;
            color: #fff;
            border: none;
            border-radius: 28px;
            padding: 12px 22px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(58,186,252,.25);
        }
        .btn-ghost {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: #fff;
            border-radius: 28px;
            border: 1px solid #dfe5ef;
            padding: 12px 22px;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(15,23,42,0.06);
        }
        .confirm-grid { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); }
        .confirm-card {
            background:#fff;
            border:1px solid #e7edf5;
            border-radius:16px;
            padding:16px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .confirm-card h3 { margin:0 0 8px; font-size:16px; font-weight:800; color:#0b1f4f; }
        .confirm-card p { margin:0; color:#556177; font-size:14px; }
        .item-row {
            display:flex; align-items:center; justify-content:space-between;
            padding:10px 0; border-bottom:1px solid #eef2f7;
            color:#0f172a; font-size:14px;
        }
        .item-row:last-child { border-bottom:none; }
        .total-line { display:flex; justify-content:space-between; margin-top:8px; font-weight:700; font-size:15px; color:#0b1f4f; }
        .tracking-placeholder {
            border:1px dashed #dfe5ef;
            border-radius:12px;
            padding:12px;
            background:#f8fbff;
            color:#475569;
            font-size:14px;
        }
    </style>

    <div class="confirm-shell">
        <div class="confirm-hero">
            <div>
                <h1>Merci ! Votre commande {{ $order->number }} est enregistrée.</h1>
                <p>Un email de confirmation vient de vous être envoyé. Le suivi reste accessible depuis votre espace client.</p>
            </div>
            <div class="confirm-actions">
                <a class="btn-primary" href="{{ route('account.commandes.show', $order) }}">Voir ma commande</a>
                <a class="btn-ghost" href="{{ route('account.dashboard') }}">Retour compte</a>
            </div>
        </div>

        <div class="confirm-grid">
            <div class="confirm-card">
                <h3>Récapitulatif</h3>
                @foreach ($order->items as $item)
                    <div class="item-row">
                        <span>{{ $item->name }} × {{ $item->quantity }}</span>
                        <strong>{{ number_format($item->total_ttc, 2, ',', ' ') }} €</strong>
                    </div>
                @endforeach
                <div class="total-line">
                    <span>Total TTC</span>
                    <span>{{ number_format($order->total_ttc, 2, ',', ' ') }} €</span>
                </div>
            </div>

            <div class="confirm-card">
                <h3>Livraison & suivi</h3>
                <p>{{ data_get($order, 'shipping_address.first_name') }} {{ data_get($order, 'shipping_address.last_name') }}</p>
                <p>{{ data_get($order, 'shipping_address.line1') }}</p>
                <p>{{ data_get($order, 'shipping_address.postal_code') }} {{ data_get($order, 'shipping_address.city') }} · {{ strtoupper(data_get($order, 'shipping_address.country_code', 'FR')) }}</p>
                @if(data_get($order, 'shipping_address.phone'))
                    <p>Tél : {{ data_get($order, 'shipping_address.phone') }}</p>
                @endif
                <div class="tracking-placeholder" style="margin-top:10px;">
                    <strong>Numéro de suivi</strong><br>
                    <span>À venir dès l’expédition.</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Si la page de confirmation s'ouvre dans une fenêtre/pop-up, on renvoie l'utilisateur
        // vers cette page dans la fenêtre d'origine puis on ferme la fenêtre actuelle.
        (function() {
            if (window.opener && !window.opener.closed && window.opener.location) {
                try {
                    window.opener.location.href = window.location.href;
                } catch (e) {
                    // ignore cross-origin; au pire la pop-up reste ouverte
                }
                window.close();
            }
        })();
    </script>
@endsection

