<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation commande {{ $order->number }}</title>
</head>
<body style="font-family: 'Segoe UI', Roboto, sans-serif; background:#f5f7fb; padding:20px 0;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 24px rgba(15,23,42,0.12);">
        <tr>
            <td style="padding:20px;text-align:center;background-color:#ffffff;">
                <h1 style="color:#3abafc;font-size:24px;margin:6px 0;">Merci pour votre commande !</h1>
                <p style="margin:8px 0 0;color:#333;font-size:14px;">Commande <strong>{{ $order->number }}</strong></p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 24px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;border-radius:10px;padding:14px;">
                    <tr>
                        <td style="color:#0f172a;font-weight:700;font-size:15px;">Montant TTC</td>
                        <td style="text-align:right;color:#0b63f6;font-weight:800;font-size:15px;">{{ $total }} €</td>
                    </tr>
                    <tr>
                        <td style="color:#556177;font-size:14px;padding-top:6px;">Statut</td>
                        <td style="text-align:right;color:#0f172a;font-size:14px;padding-top:6px;">{{ $statusLabel }}</td>
                    </tr>
                    <tr>
                        <td style="color:#556177;font-size:14px;padding-top:6px;">Date</td>
                        <td style="text-align:right;color:#0f172a;font-size:14px;padding-top:6px;">{{ $order->placed_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
                <h3 style="color:#0b1f4f;font-size:16px;margin:16px 0 10px;">Récapitulatif</h3>
                <ul style="padding:0;margin:0;list-style:none;color:#0f172a;font-size:14px;">
                    @foreach($order->items as $item)
                        <li style="padding:8px 0;border-bottom:1px solid #eef2f7;">
                            <strong>{{ $item->name }}</strong><br>
                            <span style="color:#6b7280;">x{{ $item->quantity }} • {{ number_format($item->total_ttc, 2, ',', ' ') }} €</span>
                        </li>
                    @endforeach
                </ul>
                <p style="margin:16px 0 0;color:#556177;font-size:13px;">Nous préparons votre commande et vous informerons dès l’expédition.</p>
            </td>
        </tr>
        <tr>
            <td style="background:#f5f7fb;padding:12px;text-align:center;font-size:11px;color:#999;">
                Cet e-mail a été envoyé automatiquement depuis ripair.shop.
            </td>
        </tr>
    </table>
</body>
</html>
