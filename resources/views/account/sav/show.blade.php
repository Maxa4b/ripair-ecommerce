@extends('layouts.app', ['title' => $rma->rma_number])

@section('content')
    @php use Illuminate\Support\Facades\Storage; @endphp
    <style>
        @media (max-width: 1024px) {
            body {
                background: linear-gradient(180deg, #f5f7fb 0%, #eef2ff 40%, #ffffff 100%);
                overflow-x: hidden;
            }
            main.container {
                padding: 18px 0 32px !important;
            }
            .ticket-shell {
                padding: 0 12px;
                box-sizing: border-box;
            }
        }

        .ticket-shell { display: grid; gap: 16px; }
        .ticket-hero {
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
        .ticket-hero h1 { margin: 0; font-size: 22px; font-weight: 800; color: #0b1f4f; }
        .ticket-hero p { margin: 4px 0 0; color: #475569; font-size: 14px; }
        .ticket-actions { display:flex; gap:10px; flex-wrap:wrap; }
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
        .ticket-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
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
        .ticket-card h3 { margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #0b1f4f; }
        .ticket-card p { margin: 0; color: #556177; font-size: 14px; overflow-wrap:anywhere; }
        .history { display: grid; gap: 10px; margin-top: 12px; }
        .history-item {
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px;
            background: #f9fbff;
        }
        .history-item p { overflow-wrap:anywhere; }
        .history-item small { color: #94a3b8; }
        .attachments { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .attachment-chip {
            padding:8px 10px;
            border-radius:10px;
            background:#eef5ff;
            color:#0b1f4f;
            font-weight:600;
            font-size:13px;
            text-decoration:none;
            border:1px solid rgba(3,118,184,0.15);
            max-width: 100%;
            overflow-wrap: anywhere;
        }
        .reply-form { display:grid; gap:10px; }
        .reply-form label { display:grid; gap:6px; font-weight:600; color:#0f172a; }
        .reply-form .profile-input { width:100%; border-radius:12px; border:1px solid #dfe5ef; background:#f7f9fc; padding:11px 12px; }
        .reply-form textarea { min-height:100px; resize: vertical; }
        .reply-form .file-input {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px dashed #cfe4fb;
            background: #f7fbff;
            color: #0b63f6;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            justify-content: space-between;
        }
        .reply-form .file-input input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .btn-close-ticket {
            background: linear-gradient(135deg, #fef2f2, #ffe2e8);
            color: #b91c1c;
            border: 1px solid #f8caca;
            border-radius: 28px;
            padding: 12px 18px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(185,28,28,0.14);
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-close-ticket:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(185,28,28,0.18); }
        .attachment-list { display:flex; flex-wrap:wrap; gap:8px; }

        @media (max-width: 768px) {
            .ticket-hero {
                padding: 18px;
            }
            .ticket-hero h1 {
                font-size: 18px;
            }
            .ticket-actions {
                width: 100%;
                margin-left: 0 !important;
                flex-direction: column;
                align-items: stretch;
            }
            .ticket-actions .ticket-status {
                align-self: flex-start;
            }
            .ticket-actions .btn-primary {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .btn-close-ticket {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .reply-form .file-input {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
        }
    </style>

    <div class="ticket-shell">
        <div class="ticket-hero">
            <div>
                <h1>Ticket {{ $rma->rma_number }}</h1>
                <p>Commande : {{ $rma->order?->number ?? '—' }}</p>
            </div>
            @php
                $currentStatus = strtolower($rma->status?->value ?? '');
                $statusMap = [
                    'received' => ['cls' => 'status-received', 'label' => 'Reçu'],
                    'in_review' => ['cls' => 'status-processing', 'label' => 'En cours'],
                    'accepted' => ['cls' => 'status-resolved', 'label' => 'Accepté'],
                    'refunded' => ['cls' => 'status-resolved', 'label' => 'Remboursé'],
                    'replaced' => ['cls' => 'status-resolved', 'label' => 'Remplacé'],
                    'refused' => ['cls' => 'status-closed', 'label' => 'Clôturé'],
                ];
                $status = $statusMap[$currentStatus] ?? ['cls' => 'status-received', 'label' => 'Ouvert'];
                $isClosed = ($currentStatus === 'refused') && data_get($rma->metadata, 'closed_by_user');
            @endphp
            <div class="ticket-actions" style="margin-left:auto;">
                <span class="ticket-status {{ $status['cls'] }}">{{ $status['label'] }}</span>
                <a class="btn-primary" href="{{ route('account.dashboard') }}">Retour au profil</a>
            </div>
        </div>

        <div class="ticket-card">
            <h3>Détails</h3>
            <p><strong>Motif :</strong> {{ $rma->reason ?? '—' }}</p>
            <p style="margin-top:6px;"><strong>Description :</strong> {{ $rma->description ?? '—' }}</p>
        </div>

        <div class="ticket-card">
            <h3>Historique</h3>
            @if ($rma->comments->isEmpty())
                <p style="margin-top:6px;">Aucun échange pour le moment.</p>
            @else
                <div class="history">
                    @foreach ($rma->comments as $comment)
                        <div class="history-item">
                            <p style="margin:0 0 6px;">{{ $comment->comment }}</p>
                            <small>{{ $comment->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    @endforeach
                </div>
            @endif
            @if ($rma->attachments->isNotEmpty())
                <div style="margin-top:12px;">
                    <h4 style="margin:0 0 6px;font-size:14px;color:#0f172a;">Pièces jointes</h4>
                    <div class="attachment-list">
                        @foreach ($rma->attachments as $att)
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
                <button class="btn-close-ticket" type="button">Ticket clôturé</button>
            @else
                <form method="POST" action="{{ route('account.sav.comment', $rma) }}" enctype="multipart/form-data" class="reply-form">
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
                    @if ($rma->attachments->isNotEmpty())
                        <div class="attachment-list">
                            @foreach ($rma->attachments as $att)
                                <a class="attachment-chip" href="{{ route('account.sav.attachment', $att) }}" target="_blank">{{ basename($att->path) }}</a>
                            @endforeach
                        </div>
                    @endif
                    <button class="btn-primary" type="submit">Envoyer</button>
                </form>
                <form method="POST" action="{{ route('account.sav.close', $rma) }}" style="margin-top:10px;">
                    @csrf
                    <button class="btn-close-ticket" type="submit">Clôturer le ticket</button>
                </form>
            @endif
        </div>
    </div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.querySelector('[data-file-input]');
        const fileList = document.querySelector('[data-selected-files]');
        if (fileInput && fileList) {
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
        }
    });
</script>
@endpush
@endsection
