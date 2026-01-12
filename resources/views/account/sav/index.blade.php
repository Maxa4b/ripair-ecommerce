@extends('layouts.app', ['title' => 'Support / Tickets'])

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
            .tickets-shell {
                padding: 0 12px;
                box-sizing: border-box;
            }
        }

        .tickets-shell { display: grid; gap: 20px; }
        .tickets-hero {
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
        .tickets-hero h1 { margin: 0; font-size: 22px; font-weight: 800; color: #0b1f4f; }
        .tickets-hero p { margin: 4px 0 0; color: #475569; font-size: 14px; }
        .tickets-actions { display: flex; gap: 10px; flex-wrap: wrap; }
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
        .tickets-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #e7edf5;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }
        .tickets-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .tickets-table th { text-align: left; color: #6b7280; font-size: 13px; padding-bottom: 8px; }
        .tickets-table td { padding: 10px 0; border-top: 1px solid #eef2f7; font-size: 14px; color: #0f172a; }
        .tickets-table td:last-child, .tickets-table th:last-child { text-align: right; }
        .ticket-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-received { background: #eaf8ff; color: #0b6fbf; }
        .status-processing { background: #fff5e6; color: #b35b00; }
        .status-resolved { background: #e9fbf2; color: #138a47; }
        .status-closed { background: #f4f4f5; color: #444; }
        .empty-state {
            padding: 14px;
            background: #f7f9fc;
            border: 1px dashed #dce6f4;
            border-radius: 12px;
            color: #64748b;
            font-size: 14px;
        }
        .warn {
            padding: 14px;
            border-radius: 12px;
            background: #fff5e6;
            border: 1px solid #f7c99a;
            color: #9a5300;
            font-size: 14px;
        }
        .tickets-card-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }

        @media (max-width: 768px) {
            .tickets-hero {
                padding: 18px;
            }
            .tickets-hero h1 {
                font-size: 18px;
            }
            .tickets-actions {
                width: 100%;
            }
            .tickets-actions .btn-primary,
            .tickets-actions .btn-ghost {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .tickets-card {
                padding: 16px;
            }
            .tickets-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .tickets-table thead {
                display: none;
            }
            .tickets-table,
            .tickets-table tbody,
            .tickets-table tr,
            .tickets-table td {
                display: block;
                width: 100%;
            }
            .tickets-table tr {
                margin-top: 12px;
                padding: 12px;
                border-radius: 14px;
                border: 1px solid #e7edf5;
                background: #fff;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            }
            .tickets-table td {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px;
                padding: 8px 0;
                border-top: none;
                border-bottom: 1px dashed #e7edf5;
                text-align: left !important;
                overflow-wrap: anywhere;
            }
            .tickets-table td::before {
                content: attr(data-label);
                font-weight: 800;
                color: #64748b;
                font-size: 12px;
                flex: 0 0 auto;
            }
            .tickets-table td:last-child {
                border-bottom: none;
            }
            .ticket-status {
                justify-self: flex-end;
                margin-left: auto;
            }
        }
    </style>

    <div class="tickets-shell">
        <div class="tickets-hero">
            <div>
                <h1>Support / Tickets</h1>
                <p>Suivez vos demandes SAV et ouvrez un nouveau ticket.</p>
            </div>
            <div class="tickets-actions">
                @if ($savAvailable)
                    <a class="btn-primary" href="{{ route('sav.request') }}">Ouvrir un ticket</a>
                @else
                    <button type="button" class="btn-primary" disabled style="opacity:.55;cursor:not-allowed;">Ouvrir un ticket</button>
                @endif
                <a class="btn-ghost" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        @if (!$savAvailable)
            <div class="warn">Le module SAV n est pas encore configuré côté base de données. Merci de finaliser l installation ou de contacter le support.</div>
        @endif

        <div class="tickets-card">
            <div class="tickets-card-header">
                <div>
                    <h2 style="margin:0;font-size:18px;font-weight:800;color:#0b1f4f;">Mes tickets</h2>
                    <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Historique SAV</p>
                </div>
                @if ($savAvailable)
                    <a class="btn-ghost" href="{{ route('sav.request') }}">Nouveau ticket</a>
                @else
                    <button type="button" class="btn-ghost" disabled style="opacity:.55;cursor:not-allowed;">Nouveau ticket</button>
                @endif
            </div>

            @if (!$savAvailable || $requests->isEmpty())
                <div class="empty-state" style="margin-top:12px;">Aucun ticket pour le moment.</div>
            @else
                <table class="tickets-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Commande</th>
                            <th>Statut</th>
                            <th>Créé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $rma)
                            <tr>
                                <td data-label="Ticket"><a href="{{ route('account.sav.show', $rma) }}" class="btn-ghost" style="border:none;padding:0;background:transparent;color:#3abafc;font-weight:700;">{{ $rma->rma_number }}</a></td>
                                <td data-label="Commande">{{ $rma->order?->number ?? '—' }}</td>
                                <td data-label="Statut">
                                    @php
                                        $statusValue = $rma->status?->value ?? 'received';
                                        $statusMap = [
                                            'received' => ['cls' => 'status-received', 'label' => 'Reçu'],
                                            'in_review' => ['cls' => 'status-processing', 'label' => 'En cours'],
                                            'accepted' => ['cls' => 'status-resolved', 'label' => 'Accepté'],
                                            'refunded' => ['cls' => 'status-resolved', 'label' => 'Remboursé'],
                                            'replaced' => ['cls' => 'status-resolved', 'label' => 'Remplacé'],
                                            'refused' => ['cls' => 'status-closed', 'label' => 'Clôturé'],
                                        ];
                                        $statusData = $statusMap[$statusValue] ?? ['cls' => 'status-received', 'label' => 'Ouvert'];
                                    @endphp
                                    <span class="ticket-status {{ $statusData['cls'] }}">{{ $statusData['label'] }}</span>
                                </td>
                                <td data-label="Créé" style="text-align:right;">{{ $rma->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
