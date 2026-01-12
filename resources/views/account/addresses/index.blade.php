@extends('layouts.app', ['title' => 'Adresses'])

@section('content')
    @php
        $shippingAddr = $addresses->firstWhere('type', 'shipping');
        $billingAddr = $addresses->firstWhere('type', 'billing');
    @endphp
    <style>
        .address-shell { display: grid; gap: 20px; }
        .address-hero {
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
        .address-hero h1 { margin: 0; font-size: 22px; font-weight: 800; color: #0b1f4f; }
        .address-hero p { margin: 4px 0 0; color: #475569; font-size: 14px; }
        .address-actions { display: flex; gap: 10px; flex-wrap: wrap; }
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
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }
        .address-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            position: relative;
            display: grid;
            gap: 8px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #0b63f6;
            background: #eaf8ff;
        }
        .address-meta { color: #556177; font-size: 14px; margin: 0; }
        .address-name { margin: 0; font-weight: 800; color: #0b1f4f; font-size: 16px; }
        .address-actions-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-ghost {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #dfe5ef;
            padding: 10px 14px;
            font-weight: 600;
            color: #0f172a;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(15,23,42,0.06);
        }
        .btn-pill-danger {
            background: linear-gradient(135deg, #fef2f2, #ffe2e8);
            color: #b91c1c;
            border: none;
            border-radius: 28px;
            padding: 12px 26px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(185, 28, 28, 0.16);
            transition: filter .2s, transform .2s;
            cursor: pointer;
        }
        .btn-pill-danger:hover { filter: brightness(.95); transform: translateY(-1px); }
        .edit-form {
            display: grid;
            gap: 10px;
            margin-top: 10px;
            padding: 12px;
            border: 1px dashed #dce6f4;
            border-radius: 12px;
            background: #f8fbff;
        }
        .catalog-input { width: 100%; }
        .custom-select {
            position: relative;
        }
        .custom-select__btn {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #dfe5ef;
            background: #fff;
            padding: 12px 14px;
            font-size: 15px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-family: inherit;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .custom-select__btn:hover { background: #f5f8ff; }
        .custom-select__btn svg { flex-shrink: 0; }
        .custom-select__menu {
            position: absolute;
            z-index: 30;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e7edf5;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            max-height: 200px;
            overflow: auto;
            padding: 6px;
            display: none;
            font-family: inherit;
        }
        .custom-select.open .custom-select__menu { display: block; }
        .custom-select__option {
            width: 100%;
            border: none;
            background: transparent;
            padding: 10px 12px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            color: #0f172a;
            text-align: left;
            font-family: inherit;
            transition: background 0.15s ease;
        }
        .custom-select__option:hover { background: #eef3fb; }
        .two-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .alert { padding: 12px 14px; border-radius: 12px; font-size: 14px; margin: 0; }
        .alert-error { background: #fff2f2; border: 1px solid #fecdd3; color: #b91c1c; }
        .alert-success { background: #ecfdf3; border: 1px solid #bbf7d0; color: #166534; }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 50;
        }
        .modal.open { display: flex; }
        .modal-content {
            background: #fff;
            border-radius: 18px;
            padding: 18px;
            width: min(540px, 100%);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
            border: 1px solid #e7edf5;
        }
        .modal-head {
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:12px;
        }
        .modal-head h3 { margin:0; font-size:17px; font-weight:800; color:#0b1f4f; }
        .modal-close {
            border:none;
            background:transparent;
            cursor:pointer;
            padding:6px;
        }
    </style>

    <div class="address-shell">
        <div class="address-hero">
            <div>
                <h1>Mes adresses</h1>
                <p>Livraison et facturation.</p>
            </div>
            <div class="address-actions">
                <a class="btn-primary" href="#add-address">Ajouter une adresse</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Merci de corriger :</strong>
                <ul style="margin:6px 0 0 16px; padding:0; list-style:disc;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="address-grid">
            @forelse ($addresses as $address)
                @php
                    $typeLabels = ['shipping' => 'Livraison', 'billing' => 'Facturation', 'workshop' => 'Atelier'];
                    $typeLabel = $typeLabels[$address->type] ?? $address->type;
                @endphp
                <div class="address-card" id="address-{{ $address->id }}">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <span class="badge">{{ $typeLabel }}</span>
                    </div>
                    <p class="address-name">{{ $address->first_name }} {{ $address->last_name }}</p>
                    <p class="address-meta">{{ $address->line1 }}</p>
                    <p class="address-meta">{{ $address->postal_code }} {{ $address->city }} · {{ strtoupper($address->country_code) }}</p>
                    @if($address->phone)
                        <p class="address-meta">Tél : {{ $address->phone }}</p>
                    @endif
                    <div class="address-actions-row">
                        <button class="btn" type="button" data-modal-open="modal-{{ $address->id }}">Modifier</button>
                        <form method="POST" action="{{ route('account.adresses.destroy', $address) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-pill-danger" type="submit">Supprimer</button>
                        </form>
                    </div>
                </div>
                <div class="modal" id="modal-{{ $address->id }}">
                    <div class="modal-content">
                        <div class="modal-head">
                            <h3>Modifier l adresse</h3>
                            <button class="modal-close" type="button" data-modal-close="modal-{{ $address->id }}">
                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="#0f172a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('account.adresses.update', $address) }}" class="edit-form" id="form-{{ $address->id }}" style="display:grid;">
                            @csrf
                            @method('PUT')
                            @php $typeLabel = $address->type === 'billing' ? 'Facturation' : 'Livraison'; @endphp
                            <div class="two-cols" style="align-items:center;">
                                <div class="badge">{{ $typeLabel }}</div>
                                <input type="hidden" name="type" value="{{ $address->type }}">
                            </div>
                            <div class="two-cols">
                                <input class="catalog-input" name="first_name" placeholder="Prénom" value="{{ $address->first_name }}" required>
                                <input class="catalog-input" name="last_name" placeholder="Nom" value="{{ $address->last_name }}" required>
                            </div>
                            <input class="catalog-input" name="line1" placeholder="Adresse" value="{{ $address->line1 }}" required>
                            <div class="two-cols">
                                <input class="catalog-input" name="postal_code" placeholder="Code postal" value="{{ $address->postal_code }}" required>
                                <input class="catalog-input" name="city" placeholder="Ville" value="{{ $address->city }}" required>
                            </div>
                            <div class="two-cols">
                                <input class="catalog-input" name="country_code" placeholder="Pays" value="{{ $address->country_code }}" required>
                                <input class="catalog-input" name="phone" placeholder="Téléphone" value="{{ $address->phone }}">
                            </div>
                            <div style="display:flex;justify-content:flex-end;">
                                <button class="btn-primary" type="submit">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="alert alert-error" style="background:#f7f9fc;border-color:#e7edf5;color:#0f172a;">Aucune adresse enregistrée.</div>
            @endforelse
        </div>

        <div class="address-card" id="add-address">
            <h2 style="margin:0 0 6px;font-size:18px;font-weight:800;color:#0b1f4f;">Ajouter une adresse</h2>
            <p style="margin:0 0 12px;color:#6b7280;font-size:14px;">Livraison, facturation ou retrait atelier.</p>
            <form method="POST" action="{{ route('account.adresses.store') }}" class="edit-form" style="display:grid;">
                @csrf
                <div class="two-cols">
                    <div class="custom-select" data-select>
                        <input type="hidden" name="type" value="shipping">
                        <button type="button" class="custom-select__btn" data-select-toggle>
                            <span data-select-label>Livraison</span>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="custom-select__menu">
                            <button type="button" class="custom-select__option" data-select-value="shipping" data-select-text="Livraison">Livraison</button>
                            <button type="button" class="custom-select__option" data-select-value="billing" data-select-text="Facturation">Facturation</button>
                        </div>
                    </div>
                </div>
                <div class="two-cols" id="duplicate-block">
                    <label style="display:flex;align-items:center;gap:10px;color:#0f172a;font-size:14px;">
                        <input type="checkbox" name="duplicate_billing" value="1" style="width:18px;height:18px;">
                        Utiliser aussi pour la facturation
                    </label>
                </div>
                <div class="two-cols">
                    <input class="catalog-input" name="first_name" placeholder="Prénom" required>
                    <input class="catalog-input" name="last_name" placeholder="Nom" required>
                </div>
                <input class="catalog-input" name="line1" placeholder="Adresse" required>
                <div class="two-cols">
                    <input class="catalog-input" name="postal_code" placeholder="Code postal" required>
                    <input class="catalog-input" name="city" placeholder="Ville" required>
                </div>
                <div class="two-cols">
                    <input class="catalog-input" name="country_code" placeholder="Pays" required>
                    <input class="catalog-input" name="phone" placeholder="Téléphone">
                </div>
                <button class="btn" type="submit" style="width:100%;margin-top:6px;">Ajouter l'adresse</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const closeAllSelects = () => {
                document.querySelectorAll('[data-select]').forEach(sel => sel.classList.remove('open'));
            };
            const closeAllModals = () => {
                document.querySelectorAll('.modal').forEach(m => m.classList.remove('open'));
            };
            const deleteForms = document.querySelectorAll('form[action*="adresses"][method="POST"][onsubmit]');
            deleteForms.forEach(form => form.removeAttribute('onsubmit'));

            document.querySelectorAll('[data-modal-open]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-modal-open');
                    const modal = document.getElementById(targetId);
                    if (!modal) return;
                    closeAllModals();
                    // sync label with current hidden value
                    modal.querySelectorAll('[data-select]').forEach(select => {
                        const hidden = select.querySelector('input[type="hidden"]');
                        const label = select.querySelector('[data-select-label]');
                        if (hidden && label) {
                            label.textContent = hidden.value === 'billing' ? 'Facturation' : 'Livraison';
                        }
                    });
                    modal.classList.add('open');
                });
            });

            document.querySelectorAll('[data-modal-close]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-modal-close');
                    const modal = document.getElementById(target);
                    if (modal) modal.classList.remove('open');
                });
            });

            const formStates = {
                shipping: {
                    first_name: "{{ $shippingAddr->first_name ?? '' }}",
                    last_name: "{{ $shippingAddr->last_name ?? '' }}",
                    line1: "{{ $shippingAddr->line1 ?? '' }}",
                    postal_code: "{{ $shippingAddr->postal_code ?? '' }}",
                    city: "{{ $shippingAddr->city ?? '' }}",
                    country_code: "{{ $shippingAddr->country_code ?? '' }}",
                    phone: "{{ $shippingAddr->phone ?? '' }}",
                },
                billing: {
                    first_name: "{{ $billingAddr->first_name ?? '' }}",
                    last_name: "{{ $billingAddr->last_name ?? '' }}",
                    line1: "{{ $billingAddr->line1 ?? '' }}",
                    postal_code: "{{ $billingAddr->postal_code ?? '' }}",
                    city: "{{ $billingAddr->city ?? '' }}",
                    country_code: "{{ $billingAddr->country_code ?? '' }}",
                    phone: "{{ $billingAddr->phone ?? '' }}",
                },
            };
            const fieldNames = ['first_name','last_name','line1','postal_code','city','country_code','phone'];
            const addForm = document.querySelector('#add-address form');
            const addHiddenType = addForm?.querySelector('input[name="type"]');
            const duplicateBlock = document.getElementById('duplicate-block');

            const fillForm = (form, type) => {
                if (!form) return;
                fieldNames.forEach(name => {
                    const input = form.querySelector(`[name="${name}"]`);
                    if (!input) return;
                    input.value = formStates[type]?.[name] || '';
                });
            };

            const saveFormState = (form, type) => {
                if (!form || !type) return;
                formStates[type] = {};
                fieldNames.forEach(name => {
                    const input = form.querySelector(`[name="${name}"]`);
                    formStates[type][name] = input?.value || '';
                });
            };

            const duplicateCheckbox = document.querySelector('input[name="duplicate_billing"]');
            const cloneToBilling = () => {
                saveFormState(addForm, 'shipping');
                formStates.billing = { ...formStates.shipping };
                if (addHiddenType && addHiddenType.value === 'billing') {
                    fillForm(addForm, 'billing');
                }
            };
            duplicateCheckbox?.addEventListener('change', () => {
                if (duplicateCheckbox.checked) {
                    cloneToBilling();
                }
            });

            document.querySelectorAll('[data-select]').forEach(select => {
                const hidden = select.querySelector('input[type="hidden"]');
                const toggle = select.querySelector('[data-select-toggle]');
                const label = select.querySelector('[data-select-label]');
                const options = select.querySelectorAll('[data-select-value]');
                const parentForm = select.closest('form');
                // Charger les valeurs initiales dans la modale ou le formulaire d'ajout
                fillForm(parentForm, hidden?.value || 'shipping');

                toggle?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = select.classList.contains('open');
                    closeAllSelects();
                    if (!isOpen) select.classList.add('open');
                });

                options.forEach(opt => {
                    opt.addEventListener('click', (e) => {
                        e.preventDefault();
                        const val = opt.getAttribute('data-select-value');
                        const txt = opt.getAttribute('data-select-text') || opt.textContent.trim();
                        const currentType = hidden?.value || 'shipping';
                        saveFormState(parentForm, currentType);
                        if (parentForm === addForm && val === 'billing' && duplicateCheckbox?.checked) {
                            cloneToBilling();
                        }
                        if (hidden) hidden.value = val || '';
                        if (label) label.textContent = txt;
                        select.classList.remove('open');
                        fillForm(parentForm, val || 'shipping');
                    });
                });
            });

            document.addEventListener('click', () => closeAllSelects());
            document.addEventListener('click', (e) => {
                if (e.target.classList && e.target.classList.contains('modal')) {
                    e.target.classList.remove('open');
                }
            });

            // init
            fillForm(addForm, addHiddenType?.value || 'shipping');
        });
    </script>
@endsection
