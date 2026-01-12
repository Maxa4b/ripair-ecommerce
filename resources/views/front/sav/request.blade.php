@extends('layouts.app', ['title' => 'SAV & RMA'])

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
            .services {
                padding: 0 12px;
                box-sizing: border-box;
            }
            .support-card {
                padding: 1.15rem;
                border-radius: 20px;
            }
        }

        @media (max-width: 768px) {
            .services .support-card .product-card-list {
                margin: 12px 0 0;
                padding: 0;
                list-style: none;
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .services .support-card .product-card-list > li.catalog-card {
                padding: 12px 14px !important;
                border-radius: 16px;
                border: 1px solid rgba(15, 23, 42, 0.08);
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                width: 100%;
                box-sizing: border-box;
            }
            .services .support-card .product-card-list > li.catalog-card strong {
                display: block;
                font-size: 14px;
                overflow-wrap: anywhere;
            }
            .services .support-card .product-card-list > li.catalog-card p {
                margin: 4px 0 0;
                font-size: 12px;
                color: #64748b;
            }
            .services .support-card .product-card-list > li.catalog-card .price {
                white-space: nowrap;
                font-weight: 900;
                color: #0b1f4f;
                font-size: 14px;
            }
        }

        .custom-select {
            position: relative;
        }
        .custom-select__btn {
            width: 100%;
            border-radius: 12px;
            border: 1px solid #dfe5ef;
            background: #f7f9fc;
            padding: 12px 14px;
            font-size: 15px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .custom-select__btn:hover {
            background: #f1f5fb;
        }
        .custom-select__btn svg {
            flex-shrink: 0;
        }
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
            max-height: 240px;
            overflow: auto;
            padding: 8px;
            display: none;
        }
        .custom-select.open .custom-select__menu {
            display: block;
        }
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
        }
        .custom-select__option:hover {
            background: #f1f5fb;
        }
        .custom-select__option.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .custom-select__label {
            display: grid;
            gap: 2px;
        }
        .custom-select__meta {
            color: #64748b;
            font-size: 13px;
        }
        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        .custom-checkbox input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            aspect-ratio: 1 / 1;
            padding: 0;
            display: inline-block;
            border-radius: 4px;
            border: 1px solid #dfe5ef;
            background: #f7f9fc;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
            position: relative;
            cursor: pointer;
        }
        .custom-checkbox input[type="checkbox"]:checked {
            background: #3abafc;
            border-color: #3abafc;
            box-shadow: 0 10px 22px rgba(58, 186, 252, 0.35);
        }
        .custom-checkbox input[type="checkbox"]:checked::after {
            content: "";
            position: absolute;
            left: 6px;
            top: 2px;
            width: 6px;
            height: 12px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .custom-checkbox span {
            color: #0f172a;
            font-size: 14px;
        }

        .attachment-list { display:flex; flex-wrap:wrap; gap:8px; }
        .attachment-chip {
            padding: 8px 10px;
            border-radius: 10px;
            background: #eef5ff;
            color: #0b1f4f;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid rgba(3,118,184,0.15);
            max-width: 100%;
            overflow-wrap: anywhere;
        }
        .file-input {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px dashed #cfe4fb;
            background: #f7fbff;
            color: #0b63f6;
            font-weight: 800;
            cursor: pointer;
            width: 100%;
        }
        .file-input input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .ticket-shell { display: grid; gap: 16px; }
        .ticket-hero {
            background: linear-gradient(135deg, #f7fbff, #e6f7ff);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5edf7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .ticket-hero h1 { margin: 0; font-size: 18px; font-weight: 800; color: #0b1f4f; }
        .ticket-hero p { margin: 4px 0 0; color: #475569; font-size: 14px; }
        .ticket-actions { display:flex; gap:10px; flex-wrap:wrap; width: 100%; }
        .ticket-actions .btn-primary { width: 100%; text-align:center; justify-content:center; }
        .ticket-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 800;
            width: fit-content;
        }
        .status-received { background: #eaf8ff; color: #0b6fbf; }
        .status-processing { background: #fff5e6; color: #b35b00; }
        .status-resolved { background: #e9fbf2; color: #138a47; }
        .status-closed { background: #f4f4f5; color: #444; }
        .ticket-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .ticket-card h3 { margin: 0 0 6px; font-size: 16px; font-weight: 800; color: #0b1f4f; }
        .ticket-card p { margin: 0; color: #556177; font-size: 14px; overflow-wrap:anywhere; }
        .history { display: grid; gap: 10px; margin-top: 12px; }
        .history-item {
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px;
            background: #f9fbff;
        }
        .history-item small { color: #94a3b8; }
        .reply-form { display:grid; gap:10px; }
        .reply-form label { display:grid; gap:6px; font-weight:700; color:#0f172a; }
        .reply-form .profile-input { width:100%; border-radius:12px; border:1px solid #dfe5ef; background:#f7f9fc; padding:11px 12px; }
        .reply-form textarea { min-height:100px; resize: vertical; }
        .btn-close-ticket {
            background: linear-gradient(135deg, #fef2f2, #ffe2e8);
            color: #b91c1c;
            border: 1px solid #f8caca;
            border-radius: 28px;
            padding: 12px 18px;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(185,28,28,0.14);
            cursor: pointer;
        }

        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            margin: 0;
            border: 1px solid transparent;
        }
        .alert-error { background: #fff2f2; color: #b91c1c; border-color: #fecdd3; }
        .alert-success { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
        .alert ul { margin: 8px 0 0; padding-left: 18px; }
    </style>
    <section class="services">
        <div style="display:grid;gap:10px;margin-bottom:12px;">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    <div style="font-weight:800;">Impossible d’envoyer</div>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        @if($ticket)
            <div class="ticket-shell">
                <div class="ticket-hero">
                    <div>
                        <h1>Ticket {{ $ticket->rma_number }}</h1>
                        <p>Commande : {{ $ticket->order?->number ?? '—' }}</p>
                    </div>
                    @php
                        $currentStatus = strtolower($ticket->status?->value ?? '');
                        $statusMap = [
                            'received' => ['cls' => 'status-received', 'label' => 'Reçu'],
                            'in_review' => ['cls' => 'status-processing', 'label' => 'En cours'],
                            'accepted' => ['cls' => 'status-resolved', 'label' => 'Accepté'],
                            'refunded' => ['cls' => 'status-resolved', 'label' => 'Remboursé'],
                            'replaced' => ['cls' => 'status-resolved', 'label' => 'Remplacé'],
                            'refused' => ['cls' => 'status-closed', 'label' => 'Clôturé'],
                        ];
                        $status = $statusMap[$currentStatus] ?? ['cls' => 'status-received', 'label' => 'Ouvert'];
                        $isClosed = ($currentStatus === 'refused') && data_get($ticket->metadata, 'closed_by_user');
                    @endphp
                    <div class="ticket-actions">
                        <span class="ticket-status {{ $status['cls'] }}">{{ $status['label'] }}</span>
                        <a class="btn" href="{{ route('sav.request') }}" style="width:100%;background:#fff;color:#0b1f4f;border:1px solid #dbe7f5;box-shadow:0 10px 22px rgba(15,23,42,0.06);">Nouveau ticket</a>
                    </div>
                </div>

                <div class="ticket-card">
                    <h3>Détails</h3>
                    <p><strong>Motif :</strong> {{ $ticket->reason ?? '—' }}</p>
                    <p style="margin-top:6px;"><strong>Description :</strong> {{ $ticket->description ?? '—' }}</p>
                </div>

                <div class="ticket-card">
                    <h3>Historique</h3>
                    @if ($ticket->comments->isEmpty())
                        <p style="margin-top:6px;">Aucun échange pour le moment.</p>
                    @else
                        <div class="history">
                            @foreach ($ticket->comments as $comment)
                                <div class="history-item">
                                    <p style="margin:0 0 6px;">{{ $comment->comment }}</p>
                                    <small>{{ $comment->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if ($ticket->attachments->isNotEmpty())
                        <div style="margin-top:12px;">
                            <h4 style="margin:0 0 6px;font-size:14px;color:#0f172a;">Pièces jointes</h4>
                            <div class="attachment-list">
                                @foreach ($ticket->attachments as $att)
                                    <a class="attachment-chip" href="{{ route('account.sav.attachment', $att) }}" target="_blank">{{ basename($att->path) }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="ticket-card">
                    <h3>Nouveau message</h3>
                    @if($isClosed)
                        <p style="margin:8px 0;color:#6b7280;">Ticket clôturé. Vous ne pouvez plus ajouter de messages.</p>
                        <button class="btn-close-ticket" type="button" style="width:100%;">Ticket clôturé</button>
                    @else
                        <form method="POST" action="{{ route('account.sav.comment', $ticket) }}" enctype="multipart/form-data" class="reply-form">
                            @csrf
                            <label>
                                <span>Message</span>
                                <textarea name="comment" class="profile-input" rows="3" required placeholder="Expliquez votre problème ou ajoutez des précisions..."></textarea>
                            </label>
                            <label>
                                <span>Pièces jointes (JPG, PNG, PDF)</span>
                                <span class="file-input">
                                    <span>Ajouter un fichier</span>
                                    <span style="font-size:12px;color:#475569;">JPG, PNG, PDF</span>
                                    <input type="file" name="attachments[]" multiple data-file-input accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf">
                                </span>
                            </label>
                            <div class="attachment-list" data-selected-files></div>
                            <button class="btn" type="submit" style="width:100%;">Envoyer</button>
                        </form>
                        <form method="POST" action="{{ route('account.sav.close', $ticket) }}" style="margin-top:10px;">
                            @csrf
                            <button class="btn-close-ticket" type="submit" style="width:100%;">Clôturer le ticket</button>
                        </form>
                    @endif
                </div>
            </div>
        @else
        <div class="support-grid">
            <div class="support-card">
                <h2 class="section-title" style="text-align:left;margin-top:0;">Mes commandes</h2>
                <ul class="product-card-list">
                    @foreach ($orders as $order)
                        <li class="catalog-card" style="padding:1rem;">
                            <div>
                                <strong>{{ $order->number }}</strong>
                                <p>{{ $order->status->label() }}</p>
                            </div>
                            <div class="price">{{ number_format($order->total_ttc, 2, ',', ' ') }} €</div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="support-card">
                <h2 class="section-title" style="text-align:left;margin-top:0;">Ouvrir une demande</h2>
                <form method="POST" action="{{ route('sav.request.store') }}" enctype="multipart/form-data" class="filter-card" style="box-shadow:none;border:none;padding:0;">
                    @csrf
                    <div class="filter-group">
                        <label style="display:block;font-weight:700;margin-bottom:6px;">Commande</label>
                        <div class="custom-select" data-dropdown="order">
                            <input type="hidden" name="order_number" value="{{ old('order_number', $orders->first()->number ?? '') }}">
                            <button type="button" class="custom-select__btn" data-dropdown-toggle="order">
                                <span class="custom-select__label">
                                    @if($orders->count())
                                        {{ $orders->first()->number }} — {{ $orders->first()->placed_at?->format('d/m/Y') }} — {{ number_format($orders->first()->total_ttc, 2, ',', ' ') }} €
                                    @else
                                        Aucune commande disponible
                                    @endif
                                </span>
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div class="custom-select__menu">
                                @forelse ($orders as $order)
                                    <button type="button"
                                            class="custom-select__option"
                                            data-value="{{ $order->number }}"
                                            data-label="{{ $order->number }} — {{ $order->placed_at?->format('d/m/Y') }} — {{ number_format($order->total_ttc, 2, ',', ' ') }} €">
                                        <span class="custom-select__label">
                                            <strong>{{ $order->number }}</strong>
                                            <span class="custom-select__meta">{{ $order->placed_at?->format('d/m/Y') }} — {{ number_format($order->total_ttc, 2, ',', ' ') }} €</span>
                                        </span>
                                    </button>
                                @empty
                                    <button type="button" class="custom-select__option disabled">Aucune commande disponible</button>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label style="display:block;font-weight:700;margin-bottom:6px;">Produit concerné</label>
                        <div class="custom-select" data-dropdown="item">
                            @php
                                $firstItem = $orders->flatMap->items->first();
                                $translateColor = function ($color) {
                                    $map = [
                                        'black' => 'Noir',
                                        'white' => 'Blanc',
                                        'red' => 'Rouge',
                                        'blue' => 'Bleu',
                                        'green' => 'Vert',
                                        'yellow' => 'Jaune',
                                        'purple' => 'Violet',
                                        'pink' => 'Rose',
                                        'orange' => 'Orange',
                                        'gray' => 'Gris',
                                        'grey' => 'Gris',
                                        'silver' => 'Argent',
                                        'gold' => 'Or',
                                        'teal' => 'Bleu sarcelle',
                                        'navy' => 'Bleu marine',
                                        'beige' => 'Beige',
                                        'brown' => 'Marron',
                                        'cream' => 'Crème',
                                    ];
                                    $key = strtolower(trim((string) $color));
                                    return $map[$key] ?? ucfirst($color);
                                };
                                $extractModel = function ($item) {
                                    if (! $item) {
                                        return null;
                                    }
                                    $snap = $item->variant_snapshot ?? [];
                                    $snapAttrs = $snap['attributes'] ?? [];
                                    $variantAttrs = $item->variant?->attributes ?? [];
                                    $productAttrs = $item->product?->attributes ?? [];
                                    $productMeta = $item->product?->meta ?? [];

                                    return $snap['model']
                                        ?? $snap['model_name']
                                        ?? $snap['device']
                                        ?? $snap['device_name']
                                        ?? $snapAttrs['model']
                                        ?? $variantAttrs['model']
                                        ?? $productAttrs['model']
                                        ?? $productMeta['model']
                                        ?? $productMeta['device_model']
                                        ?? null;
                                };

                                $firstBrand = $firstItem?->product?->brand?->name;
                                $firstModel = $extractModel($firstItem);
                                $firstColor = $firstItem?->product?->color;
                                $firstSnapshot = $firstItem?->variant_snapshot ?? [];
                                $firstVariantColor = $firstItem?->variant?->color ?? ($firstSnapshot['color'] ?? null);
                                $firstName = $firstItem?->product?->name ?? 'Article';
                                $colorLabel = $firstVariantColor ?: $firstColor;
                                $firstLabelParts = array_filter([$firstBrand, $firstModel, $firstName, $colorLabel ? $translateColor($colorLabel) : null]);
                                $firstLabel = $firstItem ? trim(implode(' ', $firstLabelParts)) . ' (x' . $firstItem->quantity . ')' : null;
                            @endphp
                            <input type="hidden" name="order_item_id" value="{{ old('order_item_id', $firstItem->id ?? '') }}">
                            <button type="button" class="custom-select__btn" data-dropdown-toggle="item">
                                <span class="custom-select__label">
                                    {{ $firstLabel ?? 'Aucun produit disponible' }}
                                </span>
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div class="custom-select__menu">
                                @php $hasItems = false; @endphp
                                @foreach ($orders as $order)
                                    @if($order->items->count())
                                        @php $hasItems = true; @endphp
                                        <div style="padding:6px 8px;color:#94a3b8;font-size:12px;text-transform:uppercase;">Commande {{ $order->number }}</div>
                                        @foreach ($order->items as $item)
                                            @php
                                                $brand = $item->product?->brand?->name ?? '';
                                                $snapshot = $item->variant_snapshot ?? [];
                                                $variantColor = $item->variant?->color ?? ($snapshot['color'] ?? null);
                                                $rawColor = $variantColor ?: ($item->product?->color ?? '');
                                                $color = $rawColor ? $translateColor($rawColor) : '';
                                                $modelName = $extractModel($item);
                                                $name = $item->product?->name ?? ($item->name ?? 'Article');
                                                $meta = trim(implode(' ', array_filter([$brand, $modelName, $color])));
                                                $label = trim(implode(' ', array_filter([$brand, $modelName, $name, $color]))) . ' (x' . $item->quantity . ')';
                                            @endphp
                                            <button type="button"
                                                    class="custom-select__option"
                                                    data-value="{{ $item->id }}"
                                                    data-label="{{ $label }}">
                                                <span class="custom-select__label">
                                                    <strong>{{ $name }}</strong>
                                                    <span class="custom-select__meta">{{ $meta }} • x{{ $item->quantity }}</span>
                                                </span>
                                            </button>
                                        @endforeach
                                    @endif
                                @endforeach
                                @if (!$hasItems)
                                    <button type="button" class="custom-select__option disabled">Aucun produit disponible</button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <select name="reason" class="catalog-input">
                            <option value="defaut">Défaut produit</option>
                            <option value="incompatible">Incompatible</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <textarea name="description" placeholder="Expliquez votre problème (message initial)" rows="4" class="catalog-input" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="filter-group">
                        <label style="display:block;font-weight:700;margin-bottom:6px;">Pièces jointes (JPG, PNG, PDF)</label>
                        <span class="file-input">
                            <span>Ajouter un fichier</span>
                            <span style="font-size:12px;color:#475569;">JPG, PNG, PDF</span>
                            <input type="file" name="attachments[]" multiple data-file-input accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf">
                        </span>
                        <div class="attachment-list" data-selected-files style="margin-top:8px;"></div>
                    </div>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="conditions" required>
                        <span>Film de protection intact et produit non monté.</span>
                    </label>
                    <button class="btn" style="width:100%;margin-top:1rem;">Créer le ticket</button>
                </form>
            </div>
        </div>
        @endif
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropdowns = document.querySelectorAll('[data-dropdown]');

            dropdowns.forEach((wrap) => {
                const hidden = wrap.querySelector('input[type="hidden"]');
                const btn = wrap.querySelector('[data-dropdown-toggle]');
                const menu = wrap.querySelector('.custom-select__menu');
                const options = wrap.querySelectorAll('.custom-select__option:not(.disabled)');

                const closeAll = () => {
                    dropdowns.forEach(d => d.classList.remove('open'));
                };

                btn?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = wrap.classList.contains('open');
                    closeAll();
                    if (!isOpen) wrap.classList.add('open');
                });

                options.forEach(opt => {
                    opt.addEventListener('click', (e) => {
                        e.preventDefault();
                        const value = opt.getAttribute('data-value') || '';
                        const label = opt.getAttribute('data-label') || opt.textContent.trim();
                        if (hidden) hidden.value = value;
                        if (btn) {
                            const labelSpan = btn.querySelector('.custom-select__label');
                            if (labelSpan) labelSpan.textContent = label;
                        }
                        wrap.classList.remove('open');
                    });
                });
            });

            document.addEventListener('click', () => {
                document.querySelectorAll('.custom-select').forEach(d => d.classList.remove('open'));
            });

            document.querySelectorAll('[data-file-input]').forEach((fileInput) => {
                const form = fileInput.closest('form');
                const fileList = form ? form.querySelector('[data-selected-files]') : null;
                if (!fileList) return;
                fileInput.addEventListener('change', () => {
                    fileList.innerHTML = '';
                    const files = Array.from(fileInput.files || []);
                    files.forEach((file) => {
                        const chip = document.createElement('span');
                        chip.className = 'attachment-chip';
                        chip.textContent = file.name;
                        fileList.appendChild(chip);
                    });
                });
            });
        });
    </script>
@endsection
