@extends('layouts.app', ['title' => 'Espace PRO'])

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Bienvenue {{ $user->company_name }}</h1>
        <p class="text-sm text-slate-500">Total commandes: {{ number_format($metrics['orders'], 0, ',', ' ') }} | CA: {{ number_format($metrics['total_spend'], 2, ',', ' ') }} €</p>
        <a href="{{ route('pro.pricing') }}" class="mt-4 inline-flex rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Voir mes remises</a>
        <a href="{{ route('pro.orders.export') }}" class="mt-2 inline-flex rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Exporter commandes</a>
    </div>
@endsection
