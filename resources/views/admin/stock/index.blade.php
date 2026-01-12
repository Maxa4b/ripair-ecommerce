@extends('layouts.admin', ['title' => 'Stock'])

@section('content')
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Nouveau mouvement</h2>
        <form method="POST" action="{{ route('admin.stocks-mouvements.store') }}" class="mt-4 grid gap-4 md:grid-cols-4">
            @csrf
            <input name="product_variant_id" placeholder="ID variante" class="rounded border-slate-200">
            <select name="type" class="rounded border-slate-200">
                <option value="in">Entrée</option>
                <option value="out">Sortie</option>
                <option value="return">Retour</option>
            </select>
            <input name="quantity" type="number" value="1" class="rounded border-slate-200">
            <input name="notes" placeholder="Motif" class="rounded border-slate-200 md:col-span-2">
            <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white md:col-span-2">Ajouter</button>
        </form>
    </section>

    <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Historique</h2>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th>Produit</th>
                    <th>Type</th>
                    <th>Qté</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($movements as $movement)
                    <tr class="border-t border-slate-100">
                        <td>{{ $movement->variant->product->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($movement->type->value ?? $movement->type) }}</td>
                        <td>{{ $movement->quantity }}</td>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    </section>
@endsection
