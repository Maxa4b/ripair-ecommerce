@extends('layouts.admin', ['title' => 'Reporting'])

@section('content')
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Export comptable</h2>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th>Commande</th>
                    <th>Date</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr class="border-t border-slate-100">
                        <td>{{ $order->number }}</td>
                        <td>{{ $order->placed_at?->format('d/m/Y') }}</td>
                        <td>{{ number_format($order->total_ttc, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
