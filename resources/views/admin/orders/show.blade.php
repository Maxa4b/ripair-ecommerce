@extends('layouts.admin', ['title' => "Commande {$order->number}"])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Articles</h2>
            <table class="mt-4 w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-t border-slate-100">
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->total_ttc, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Mise à jour statut</h2>
            <form method="POST" action="{{ route('admin.commandes.update', $order) }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')
                <select name="status" class="w-full rounded border-slate-200">
                    @foreach (\App\Enums\OrderStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <input name="tracking_number" value="{{ $order->tracking_number }}" placeholder="Tracking" class="w-full rounded border-slate-200">
                <input name="carrier_name" value="{{ $order->carrier_name }}" placeholder="Transporteur" class="w-full rounded border-slate-200">
                <textarea name="internal_note" placeholder="Note interne" class="w-full rounded border-slate-200"></textarea>
                <button class="w-full rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Mettre à jour</button>
            </form>
        </section>
    </div>
@endsection
