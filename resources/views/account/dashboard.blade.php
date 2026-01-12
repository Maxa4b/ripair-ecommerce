@extends('layouts.app', ['title' => 'Mon compte'])

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
            .account-shell {
                padding: 0 12px;
                box-sizing: border-box;
            }
        }

        .account-shell {
            display: grid;
            gap: 20px;
        }
        .account-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            border: 1px solid #e5edf7;
        }
        .account-id {
            display: flex;
            gap: 14px;
            align-items: center;
        }
        .account-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #3abafc;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 10px 26px rgba(0, 99, 246, 0.3);
        }
        .account-name {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #0b1f4f;
        }
        .account-meta {
            margin: 4px 0 0;
            color: #475569;
            font-size: 14px;
        }
        .account-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .account-chip {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #dce6f4;
            background: #fff;
            font-weight: 600;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .account-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .account-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
        }
        .account-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,167,255,0.08), rgba(29,91,255,0.04));
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        .account-card:hover::after {
            opacity: 1;
        }
        .account-card-content {
            position: relative;
            display: grid;
            gap: 10px;
        }
        .account-card h3 {
            margin: 0;
            font-size: 15px;
            color: #0b1f4f;
            font-weight: 700;
        }
        .account-card p {
            margin: 0;
            color: #556177;
            font-size: 13px;
        }
        .account-card strong {
            font-size: 24px;
            color: #0b1f4f;
        }
        .account-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #eaf8ff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #3abafc;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
        }
        .account-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #3abafc;
            font-weight: 700;
            text-decoration: none;
        }
        .orders-block {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .orders-table th {
            text-align: left;
            color: #6b7280;
            font-size: 13px;
            padding-bottom: 8px;
        }
        .orders-table td {
            padding: 10px 0;
            border-top: 1px solid #eef2f7;
            font-size: 14px;
            color: #0f172a;
        }
        .orders-table td:last-child, .orders-table th:last-child {
            text-align: right;
        }
        .empty-state {
            padding: 14px;
            background: #f7f9fc;
            border: 1px dashed #dce6f4;
            border-radius: 12px;
            color: #64748b;
            font-size: 14px;
        }
        .btn-primary {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: var(--blue, #3abafc);
            color: #fff;
            border: none;
            border-radius: 28px;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(58,186,252,.25);
            transition: filter .2s, transform .2s;
        }
        .btn-primary:hover { filter:brightness(.92); transform: translateY(-1px); }
        .btn-ghost {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: #fff;
            border-radius: 28px;
            border: 1px solid #dfe5ef;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(15,23,42,0.06);
            transition: filter .2s, transform .2s;
        }
        .btn-ghost:hover { filter:brightness(.98); transform: translateY(-1px); }
        .btn-danger {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: linear-gradient(135deg, #f87171, #dc2626);
            color: #fff;
            border: none;
            border-radius: 28px;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(220,38,38,0.22);
            transition: filter .2s, transform .2s;
        }
        .btn-danger:hover { filter:brightness(.92); transform: translateY(-1px); }
        .profile-panel {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.25s ease, opacity 0.25s ease;
            max-height: 0;
            padding: 0 18px;
            opacity: 0;
        }
        .profile-panel.open {
            max-height: 1100px;
            padding: 16px 18px 20px;
            opacity: 1;
        }
        .profile-panel h3 { margin:0 0 6px; font-size:18px; font-weight:800; color:#0b1f4f; }
        .profile-panel p { margin:0 0 12px; color:#556177; font-size:14px; }
        .profile-form-grid { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); }
        .profile-label { display:grid; gap:6px; font-weight:600; color:#0f172a; }
        .profile-input {
            width:100%;
            border-radius:12px;
            border:1px solid #dfe5ef;
            background:#f7f9fc;
            padding:11px 12px;
            font-size:14px;
        }
        .profile-input:focus {
            outline:2px solid #b8ddff;
            border-color:#7bb8ff;
            background:#fff;
        }
        .danger-zone {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px dashed #dfe5ef;
        }
        .danger-zone h4 {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 900;
            color: #b91c1c;
        }
        .danger-zone p {
            margin: 0 0 12px;
            color: #6b7280;
            font-size: 14px;
        }
        .account-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 45000;
        }
        .account-modal-backdrop.is-open {
            display: flex;
        }
        .account-modal {
            width: min(520px, 100%);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.22);
            padding: 18px;
        }
        .account-modal h4 {
            margin: 0 0 6px;
            font-size: 17px;
            font-weight: 900;
            color: #0b1f4f;
        }
        .account-modal p {
            margin: 0 0 14px;
            color: #556177;
            font-size: 14px;
        }
        .account-modal .modal-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 14px;
        }
        body.account-modal-open {
            overflow: hidden;
        }

        .orders-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .account-hero {
                grid-template-columns: 1fr;
                padding: 18px;
                gap: 14px;
            }
            .account-avatar {
                width: 48px;
                height: 48px;
                font-size: 16px;
            }
            .account-name {
                font-size: 18px;
            }
            .account-meta {
                font-size: 13px;
            }
            .account-actions {
                justify-content: flex-start;
            }
            .account-actions .account-chip,
            .account-actions .btn-primary {
                width: 100%;
                justify-content: center;
            }
            .account-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .account-card {
                padding: 14px;
            }
            .account-card h3 {
                font-size: 14px;
                line-height: 1.2;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
            }
            .account-card p {
                font-size: 12px;
            }
            .account-card strong {
                font-size: 20px;
            }
            .orders-block {
                padding: 16px;
            }
            .orders-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .orders-table thead {
                display: none;
            }
            .orders-table,
            .orders-table tbody,
            .orders-table tr,
            .orders-table td {
                display: block;
                width: 100%;
            }
            .orders-table tr {
                margin-top: 12px;
                padding: 12px;
                border-radius: 14px;
                border: 1px solid #e7edf5;
                background: #fff;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            }
            .orders-table td {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px;
                padding: 8px 0;
                border-top: none;
                border-bottom: 1px dashed #e7edf5;
                text-align: left !important;
            }
            .orders-table td::before {
                content: attr(data-label);
                font-weight: 800;
                color: #64748b;
                font-size: 12px;
                flex: 0 0 auto;
            }
            .orders-table td:last-child {
                border-bottom: none;
            }
            .profile-form-grid {
                grid-template-columns: 1fr;
            }
            .profile-actions {
                flex-direction: column;
            }
            .profile-actions .btn-primary,
            .profile-actions .btn-ghost {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .danger-zone .btn-danger {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .account-modal .modal-actions {
                flex-direction: column;
            }
            .account-modal .modal-actions .btn-ghost,
            .account-modal .modal-actions .btn-danger {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
        }

        @media (max-width: 420px) {
            .account-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="account-shell">
        <div class="account-hero">
            <div class="account-id">
                <div class="account-avatar">{{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}</div>
                <div>
                    <p class="account-name">{{ $user->first_name }} {{ $user->last_name }}</p>
                    <p class="account-meta">{{ $user->email }} @if($user->phone) • {{ $user->phone }} @endif</p>
                </div>
            </div>
            <div class="account-actions">
                <span class="account-chip">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#3abafc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l3 3"/></svg>
                    Compte {{ $user->account_type === 'pro' ? 'PRO' : 'Standard' }}
                </span>
                <button class="btn-primary" id="toggleProfileForm" type="button">Modifier mes infos</button>
            </div>
        </div>

        <div class="account-grid">
            <a class="account-card" href="{{ route('account.commandes.index') }}">
                <div class="account-card-content">
                    <span class="account-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2" ry="2"/><path d="M3 10h18"/><path d="m8 14 2 2 4-4"/></svg>
                    </span>
                    <h3>Commandes en cours</h3>
                    <p>Suivi en cours</p>
                    <strong>{{ (int) ($openOrders ?? 0) + (int) ($draftCheckoutCount ?? 0) }}</strong>
                    <span class="account-link">Voir mes commandes</span>
                </div>
            </a>
            <a class="account-card" href="{{ route('account.sav.index') }}">
                <div class="account-card-content">
                    <span class="account-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2h-4l-4 4v-4H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>
                    </span>
                    <h3>Demandes SAV</h3>
                    <p>Tickets en cours</p>
                    <strong>{{ $rmaCount }}</strong>
                    <span class="account-link">Suivre mes tickets</span>
                </div>
            </a>
            <a class="account-card" href="{{ route('account.adresses.index') }}">
                <div class="account-card-content">
                    <span class="account-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <h3>Adresses</h3>
                    <p>Livraison et facturation</p>
                    <strong>{{ $addresses?->count() ?? 0 }}</strong>
                    <span class="account-link">Gérer mes adresses</span>
                </div>
            </a>
            <a class="account-card" href="{{ route('account.factures.index') }}">
                <div class="account-card-content">
                    <span class="account-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-4"/><path d="M6 12V7a2 2 0 0 1 2-2h2"/><path d="M20 10V6a2 2 0 0 0-2-2h-2"/><path d="M4 12h16"/><path d="M18 16h.01"/><path d="M6 16h.01"/></svg>
                    </span>
                    <h3>Factures</h3>
                    <p>Reçus et téléchargements</p>
                    <strong>{{ $invoicesCount ?? 0 }}</strong>
                    <span class="account-link">Mes factures</span>
                </div>
            </a>
        </div>

        <div class="orders-block">
            <div class="orders-header">
                <div>
                    <h2 style="margin:0;font-size:18px;font-weight:800;color:#0b1f4f;">Dernières commandes</h2>
                    <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Historique récent</p>
                </div>
                <a class="account-link" href="{{ route('account.commandes.index') }}">Voir tout</a>
            </div>

            @if ($user->orders->isEmpty())
                <div class="empty-state" style="margin-top:12px;">Aucune commande pour le moment.</div>
            @else
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($user->orders as $order)
                            <tr>
                                <td data-label="Numéro"><a href="{{ route('account.commandes.show', $order) }}" class="account-link">{{ $order->number }}</a></td>
                                <td data-label="Date">{{ $order->placed_at?->format('d/m/Y') }}</td>
                                <td data-label="Statut">{{ $order->status->label() }}</td>
                                <td data-label="Total" style="text-align:right;">{{ number_format($order->total_ttc, 2, ',', ' ') }} €</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div id="profilePanel" class="profile-panel">
            <h3>Mettre à jour mes informations</h3>
            <p>Tout se fait ici sans changer de page.</p>

            @if ($errors->any())
                <div class="alert alert-error" style="margin-bottom:10px;">
                    <strong>Merci de corriger :</strong>
                    <ul style="margin:6px 0 0 16px; padding:0; list-style:disc;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('account.profile.update') }}" class="profile-form-grid">
                @csrf
                @method('PUT')
                <label class="profile-label">
                    <span>Prénom</span>
                    <input name="first_name" value="{{ old('first_name', $user->first_name) }}" class="profile-input" required>
                </label>
                <label class="profile-label">
                    <span>Nom</span>
                    <input name="last_name" value="{{ old('last_name', $user->last_name) }}" class="profile-input" required>
                </label>
                <label class="profile-label">
                    <span>E-mail</span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="profile-input" required>
                </label>
                <label class="profile-label">
                    <span>Téléphone</span>
                    <input name="phone" value="{{ old('phone', $user->phone) }}" class="profile-input">
                </label>
                <label class="profile-label">
                    <span>Société (facultatif)</span>
                    <input name="company_name" value="{{ old('company_name', $user->company_name) }}" class="profile-input">
                </label>
                <label class="profile-label">
                    <span>TVA intracom (facultatif)</span>
                    <input name="vat_number" value="{{ old('vat_number', $user->vat_number) }}" class="profile-input">
                </label>
                <label class="profile-label">
                    <span>Mot de passe (optionnel)</span>
                    <input type="password" name="password" class="profile-input" placeholder="Laissez vide pour ne pas changer">
                </label>
                <label class="profile-label">
                    <span>Confirmation</span>
                    <input type="password" name="password_confirmation" class="profile-input" placeholder="Confirmez">
                </label>
                <div class="profile-actions">
                    <button class="btn-primary" type="submit">Enregistrer</button>
                    <button class="btn-ghost" type="button" id="closeProfileForm">Fermer</button>
                </div>
            </form>

            <div class="danger-zone">
                <h4>Zone dangereuse</h4>
                <p>Supprimer votre compte est irréversible. Toutes vos données seront définitivement effacées.</p>
                <button class="btn-danger" type="button" id="openDeleteAccount">Supprimer mon compte</button>
            </div>
        </div>
    </div>

    <div class="account-modal-backdrop @if($errors->userDeletion->isNotEmpty()) is-open @endif" id="deleteAccountBackdrop" aria-hidden="{{ $errors->userDeletion->isNotEmpty() ? 'false' : 'true' }}">
        <div class="account-modal" role="dialog" aria-modal="true" aria-labelledby="deleteAccountTitle">
            <h4 id="deleteAccountTitle">Confirmer la suppression</h4>
            <p>Pour confirmer, saisissez votre mot de passe puis validez. Cette action est définitive.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
                @csrf
                @method('DELETE')
                <label class="profile-label">
                    <span>Mot de passe</span>
                    <input type="password" name="password" class="profile-input" required autocomplete="current-password">
                </label>
                @if($errors->userDeletion->isNotEmpty())
                    <div class="alert alert-error" style="margin-top:10px;">
                        {{ $errors->userDeletion->first('password') }}
                    </div>
                @endif
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" id="cancelDeleteAccount">Annuler</button>
                    <button type="submit" class="btn-danger">Supprimer définitivement</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const panel = document.getElementById('profilePanel');
            const toggle = document.getElementById('toggleProfileForm');
            const closeBtn = document.getElementById('closeProfileForm');
            const openDelete = document.getElementById('openDeleteAccount');
            const deleteBackdrop = document.getElementById('deleteAccountBackdrop');
            const cancelDelete = document.getElementById('cancelDeleteAccount');
            const deleteForm = document.getElementById('deleteAccountForm');

            const openPanel = () => panel.classList.add('open');
            const closePanel = () => panel.classList.remove('open');

            const openDeleteModal = () => {
                deleteBackdrop?.classList.add('is-open');
                if (deleteBackdrop) deleteBackdrop.style.display = '';
                document.body.classList.add('account-modal-open');
                const input = deleteForm?.querySelector('input[name="password"]');
                input?.focus?.();
            };
            const closeDeleteModal = () => {
                deleteBackdrop?.classList.remove('is-open');
                if (deleteBackdrop) deleteBackdrop.style.display = '';
                document.body.classList.remove('account-modal-open');
            };

            toggle?.addEventListener('click', () => {
                panel?.classList.toggle('open');
            });
            closeBtn?.addEventListener('click', closePanel);
            openDelete?.addEventListener('click', openDeleteModal);
            cancelDelete?.addEventListener('click', closeDeleteModal);
            deleteBackdrop?.addEventListener('click', (event) => {
                if (event.target === deleteBackdrop) {
                    closeDeleteModal();
                }
            });
            window.addEventListener('keyup', (event) => {
                if (event.key === 'Escape') {
                    closeDeleteModal();
                }
            });

            @if($errors->userDeletion->isNotEmpty())
            openPanel();
            openDeleteModal();
            @endif
        })();
    </script>
@endsection
