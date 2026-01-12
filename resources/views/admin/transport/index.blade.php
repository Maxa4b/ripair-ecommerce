@extends('layouts.admin', ['title' => 'Transport & livraison'])

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.transporteurs.create') }}" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nouveau mode</a>
    </div>
    <table class="w-full rounded-2xl bg-white text-sm shadow-sm">
        <thead>
            <tr class="text-left text-slate-500">
                <th>Nom</th>
                <th>Transporteur</th>
                <th>Type</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($methods as $method)
                <tr class="border-t border-slate-100">
                    <td>{{ $method->name }}</td>
                    <td>{{ $method->transporter?->name }}</td>
                    <td>{{ $method->type }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.transporteurs.edit', $method) }}" class="text-indigo-600">Modifier</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
