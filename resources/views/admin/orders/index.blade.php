@extends('layouts.admin', ['title' => 'Commandes'])

@section('content')
    <table class="w-full rounded-2xl bg-white text-sm shadow-sm">
        <thead>
            <tr class="text-left text-slate-500">
                <th>Numéro</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Montant</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr class="border-t border-slate-100">
                    <td>{{ $order->number }}</td>
                    <td>{{ $order->user?->full_name }}</td>
                    <td>{{ $order->status->label() }}</td>
                    <td>{{ number_format($order->total_ttc, 2, ',', ' ') }} €</td>
                    <td class="text-right">
                        <a href="{{ route('admin.commandes.show', $order) }}" class="text-indigo-600">Gérer</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        {{ $orders->links() }}
    </div>
@endsection
