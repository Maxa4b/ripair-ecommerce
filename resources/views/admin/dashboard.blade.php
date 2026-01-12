@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <div class="grid gap-6 md:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">CA global</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($metrics['revenue'] ?? 0, 2, ',', ' ') }} €</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Commandes</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $metrics['orders'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Panier moyen</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($metrics['average_cart'] ?? 0, 2, ',', ' ') }} €</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Taux de retour</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $metrics['return_rate'] ?? 0 }} %</p>
        </div>
    </div>

    <section class="mt-8 rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">CA par catégorie</h2>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th>Catégorie</th>
                    <th class="text-right">CA</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($revenueByCategory as $row)
                    <tr class="border-t border-slate-100">
                        <td>{{ $row->category }}</td>
                        <td class="text-right">{{ number_format($row->total, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
