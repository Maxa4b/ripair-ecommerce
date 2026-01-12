@extends('layouts.admin', ['title' => 'SAV'])

@section('content')
    <table class="w-full rounded-2xl bg-white text-sm shadow-sm">
        <thead>
            <tr class="text-left text-slate-500">
                <th>RMA</th>
                <th>Commande</th>
                <th>Client</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($requests as $rma)
                <tr class="border-t border-slate-100">
                    <td>{{ $rma->rma_number }}</td>
                    <td>{{ $rma->order->number }}</td>
                    <td>{{ $rma->order->user?->full_name }}</td>
                    <td>{{ ucfirst($rma->status->value) }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.sav-rma.show', $rma) }}" class="text-indigo-600">Gérer</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        {{ $requests->links() }}
    </div>
@endsection
