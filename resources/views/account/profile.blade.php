@extends('layouts.app', ['title' => 'Profil'])

@section('content')
    <style>
        .profile-shell { display: grid; gap: 20px; }
        .profile-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            border: 1px solid #e5edf7;
        }
        .profile-id {
            display: flex;
            gap: 14px;
            align-items: center;
        }
        .profile-avatar {
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
        .profile-name {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #0b1f4f;
        }
        .profile-meta {
            margin: 4px 0 0;
            color: #475569;
            font-size: 14px;
        }
        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .profile-chip {
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
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
        }
        .profile-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            display: grid;
            gap: 10px;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }
        .profile-card h3 { margin: 0; font-size: 16px; color: #0b1f4f; font-weight: 700; }
        .profile-card p {
            margin: 0;
            color: #556177;
            font-size: 14px;
        }
        .profile-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,167,255,0.08), rgba(29,91,255,0.04));
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        .profile-card:hover::after {
            opacity: 1;
        }
        .profile-card-content { position: relative; display: grid; gap: 8px; }
        .profile-card-icon {
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
        .profile-link { display: inline-flex; align-items: center; gap: 6px; color: #3abafc; font-weight: 700; text-decoration: none; }
        .profile-link-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .profile-form {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            display: grid;
            gap: 16px;
        }
        .profile-form h2 { margin: 0; font-size: 18px; font-weight: 800; color: #0b1f4f; }
        .profile-form p { margin: 0; color: #6b7280; font-size: 14px; }
        .profile-form .grid { display: grid; gap: 14px; }
        .profile-form .two { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .profile-label { display: grid; gap: 6px; font-weight: 600; color: #0f172a; }
        .profile-input {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #dfe5ef;
            background: #f7f9fc;
            padding: 11px 12px;
            font-size: 14px;
        }
        .profile-input:focus {
            outline: 2px solid #b8ddff;
            border-color: #7bb8ff;
            background: #fff;
        }
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
        .btn-ghost {
            font-family:'FontAgio','Montserrat',sans-serif;
            background: #fff;
            border-radius: 28px;
            border: 1px solid #dfe5ef;
            padding: 12px 26px;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(15,23,42,0.06);
            transition: filter .2s, transform .2s;
        }
        .btn-ghost:hover { filter:brightness(.98); transform: translateY(-1px); }
    </style>

    <div class="profile-shell">
        <div class="profile-hero">
            <div class="profile-id">
                <div class="profile-avatar">{{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}</div>
                <div>
                    <p class="profile-name">{{ $user->first_name }} {{ $user->last_name }}</p>
                    <p class="profile-meta">{{ $user->email }} @if($user->phone) • {{ $user->phone }} @endif</p>
                </div>
            </div>
            <div class="profile-actions">
                <span class="profile-chip">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0b63f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l3 3"/></svg>
                    Compte {{ $user->account_type === 'pro' ? 'PRO' : 'Standard' }}
                </span>
                <a class="btn-primary" href="#profil-form">Mettre à jour</a>
                <a class="btn-ghost" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        <div class="profile-grid">
            <a class="profile-card profile-link-card" href="{{ route('account.commandes.index') }}" target="_self">
                <div class="profile-card-content">
                    <span class="profile-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2" ry="2"/><path d="M3 10h18"/><path d="m8 14 2 2 4-4"/></svg>
                    </span>
                    <h3>Suivi des commandes</h3>
                    <p>Consultez vos commandes, statuts et factures.</p>
                    <span class="profile-link" style="padding-top:4px;">Ouvrir le suivi</span>
                </div>
            </a>
            <a class="profile-card profile-link-card" href="{{ route('account.sav.index') }}" target="_self">
                <div class="profile-card-content">
                    <span class="profile-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2h-4l-4 4v-4H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 9h8"/><path d="M8 13h5"/></svg>
                    </span>
                    <h3>Support et tickets</h3>
                    <p>Suivi SAV et demandes en cours.</p>
                    <span class="profile-link" style="padding-top:4px;">Voir mes tickets</span>
                </div>
            </a>
            <a class="profile-card profile-link-card" href="{{ route('account.adresses.index') }}" target="_self">
                <div class="profile-card-content">
                    <span class="profile-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <h3>Adresses</h3>
                    <p>Gérez vos adresses de livraison et facturation.</p>
                    <span class="profile-link" style="padding-top:4px;">Gérer mes adresses</span>
                </div>
            </a>
            <div class="profile-card">
                <div class="profile-card-content">
                    <span class="profile-card-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-4"/><path d="M6 12V7a2 2 0 0 1 2-2h2"/><path d="M20 10V6a2 2 0 0 0-2-2h-2"/><path d="M4 12h16"/><path d="M18 16h.01"/><path d="M6 16h.01"/></svg>
                    </span>
                    <h3>Factures</h3>
                    <p>Téléchargez vos factures et reçus.</p>
                    <a class="profile-link" href="{{ route('account.factures.index') }}">Mes factures</a>
                </div>
            </div>
        </div>

        <div class="profile-form" id="profil-form">
            <h2>Informations personnelles</h2>
            <p>Mettez à jour vos coordonnées pour accélérer vos commandes.</p>

            @if ($errors->any())
                <div class="alert alert-error" style="margin-bottom:14px;">
                    <strong>Merci de corriger :</strong>
                    <ul style="margin:6px 0 0 16px; padding:0; list-style:disc;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('account.profile.update') }}" class="grid two">
                @csrf
                @method('PUT')
                <label class="profile-label">
                    <span>Prénom</span>
                    <input name="first_name" value="{{ $user->first_name }}" class="profile-input" placeholder="Prénom" required>
                </label>
                <label class="profile-label">
                    <span>Nom</span>
                    <input name="last_name" value="{{ $user->last_name }}" class="profile-input" placeholder="Nom" required>
                </label>
                <label class="profile-label">
                    <span>Téléphone</span>
                    <input name="phone" value="{{ $user->phone }}" class="profile-input" placeholder="Téléphone">
                </label>
                <label class="profile-label">
                    <span>Société (facultatif)</span>
                    <input name="company_name" value="{{ $user->company_name }}" class="profile-input" placeholder="Société (facultatif)">
                </label>
                <label class="profile-label">
                    <span>TVA intracom (facultatif)</span>
                    <input name="vat_number" value="{{ $user->vat_number }}" class="profile-input" placeholder="TVA intracom (facultatif)">
                </label>
                <label class="profile-label">
                    <span>Mot de passe (optionnel)</span>
                    <input class="profile-input" type="password" name="password" placeholder="Laissez vide pour ne pas changer">
                </label>
                <label class="profile-label">
                    <span>Confirmation</span>
                    <input class="profile-input" type="password" name="password_confirmation" placeholder="Confirmez">
                </label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;grid-column: span 2;">
                    <button class="btn-primary" type="submit">Enregistrer</button>
                    <a class="btn-ghost" href="{{ route('account.dashboard') }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
