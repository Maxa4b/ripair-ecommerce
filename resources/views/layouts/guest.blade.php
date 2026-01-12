<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- RIPAIR core styles -->
        <link rel="stylesheet" href="{{ asset('css/critical.css') }}">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/ecommerce.css') }}">
        <link rel="stylesheet" href="{{ asset('css/cart-animations.css') }}">

        <!-- Vite bundle (icons, Alpine, etc.) -->
        @vite(['resources/js/app.js'])

        <style>
            body.auth-body {
                min-height: 100vh;
                margin: 0;
                background: radial-gradient(circle at 20% 20%, #f3f8ff 0, #f6f9ff 25%, #fdfefe 55%, #ffffff 100%);
                font-family: "Inter", system-ui, -apple-system, sans-serif;
                color: #0f172a;
            }
            .auth-shell {
                max-width: 1080px;
                margin: 0 auto;
                padding: 48px 16px 64px;
                display: grid;
                gap: 32px;
            }
            .auth-header {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 12px;
            }
            .auth-header img {
                height: 64px;
                width: auto;
            }
            .auth-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 24px;
                align-items: start;
            }
            .auth-card {
                background: #ffffff;
                border-radius: 20px;
                box-shadow: 0 15px 60px rgba(17, 24, 39, 0.08), 0 4px 20px rgba(17, 24, 39, 0.05);
                padding: 28px 28px 32px;
                border: 1px solid #eef2f7;
            }
            .auth-card h1 {
                margin: 0 0 6px;
                font-size: 26px;
                font-weight: 700;
                color: #0b1f4f;
            }
            .auth-card p.lead {
                margin: 0 0 18px;
                color: #475569;
                font-size: 15px;
            }
            .auth-form {
                display: grid;
                gap: 16px;
            }
            .form-row {
                display: grid;
                gap: 12px;
            }
            .form-row.two {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 12px;
            }
            label {
                font-size: 14px;
                font-weight: 600;
                color: #0f172a;
                margin-bottom: 4px;
                display: block;
            }
            .auth-input {
                width: 100%;
                border-radius: 12px;
                border: 1px solid #dfe5ef;
                background: #f7f9fc;
                padding: 12px 14px;
                font-size: 15px;
                transition: all 0.2s ease;
            }
            .auth-input:focus {
                outline: 2px solid #b8ddff;
                background: #fff;
                border-color: #7bb8ff;
                box-shadow: 0 6px 18px rgba(56, 132, 255, 0.12);
            }
            .auth-checkbox {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                color: #334155;
            }
            .auth-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-top: 4px;
                flex-wrap: wrap;
            }
            .auth-btn {
                background: linear-gradient(135deg, #3abafc, #2698d8);
                color: #fff;
                border: none;
                border-radius: 999px;
                padding: 14px 26px;
                font-size: 15px;
                font-weight: 800;
                cursor: pointer;
                box-shadow: 0 12px 28px rgba(58, 186, 252, 0.26);
                transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            }
            .auth-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 16px 36px rgba(38, 152, 216, 0.28);
                filter: brightness(0.97);
            }
            .auth-secondary {
                color: #0b63f6;
                font-weight: 600;
                text-decoration: none;
            }
            .auth-secondary:hover {
                text-decoration: underline;
            }
            .auth-footer {
                text-align: center;
                color: #6b7280;
                font-size: 14px;
            }
            .password-field {
                position: relative;
            }
            .toggle-password {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                border: none;
                background: #eef2f7;
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                padding: 0;
                transition: background 0.15s ease, box-shadow 0.15s ease;
            }
            .toggle-password:hover {
                background: #e1e8f3;
                box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
            }
            .toggle-password svg {
                width: 18px;
                height: 18px;
                stroke: #0f172a;
            }
            .alert {
                border-radius: 12px;
                padding: 12px 14px;
                font-size: 14px;
            }
            .alert-error {
                background: #fff2f2;
                color: #b91c1c;
                border: 1px solid #fecdd3;
            }
            .alert-success {
                background: #ecfdf3;
                color: #166534;
                border: 1px solid #bbf7d0;
            }
        </style>
    </head>
    <body class="auth-body" data-hide-cart-fab="true">
        <div class="auth-shell">
            <div class="auth-header">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('assets/img/logo.webp') }}" alt="RIPAIR Logo">
                </a>
            </div>

            <div class="auth-grid">
                <div class="auth-card">
                    {{ $slot }}
                </div>
                <div class="auth-card" style="background: radial-gradient(circle at 20% 20%, #f3f8ff 0, #f9fbff 45%, #ffffff 100%);">
                    <h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#0b1f4f;">Bienvenue chez RIPAIR</h2>
                    <p style="margin:0 0 14px;color:#475569;">Accédez à votre espace pour suivre vos commandes, garanties et préférences.</p>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:10px;color:#0f172a;font-size:14px;">
                        <li style="display:flex;gap:10px;align-items:flex-start;">
                            <span style="width:22px;height:22px;border-radius:50%;background:#e9f8ff;display:inline-flex;align-items:center;justify-content:center;color:#3abafc;font-weight:700;">1</span>
                            <div>
                                <strong>Suivi en temps réel</strong>
                                <p style="margin:2px 0 0;color:#64748b;">Commandes, expéditions, factures et SAV au même endroit.</p>
                            </div>
                        </li>
                        <li style="display:flex;gap:10px;align-items:flex-start;">
                            <span style="width:22px;height:22px;border-radius:50%;background:#e9f8ff;display:inline-flex;align-items:center;justify-content:center;color:#3abafc;font-weight:700;">2</span>
                            <div>
                                <strong>Infos préremplies</strong>
                                <p style="margin:2px 0 0;color:#64748b;">Adresses et préférences de paiement enregistrées.</p>
                            </div>
                        </li>
                        <li style="display:flex;gap:10px;align-items:flex-start;">
                            <span style="width:22px;height:22px;border-radius:50%;background:#e9f8ff;display:inline-flex;align-items:center;justify-content:center;color:#3abafc;font-weight:700;">3</span>
                            <div>
                                <strong>Support prioritaire</strong>
                                <p style="margin:2px 0 0;color:#64748b;">Un expert RIPAIR vous répond rapidement.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            @include('layouts.partials.footer')
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.toggle-password').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const targetId = btn.getAttribute('data-toggle');
                        const input = document.getElementById(targetId);
                        if (!input) return;
                        const isPassword = input.type === 'password';
                        input.type = isPassword ? 'text' : 'password';
                    });
                });
            });
        </script>
    </body>
</html>
