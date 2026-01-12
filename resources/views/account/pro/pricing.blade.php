@extends('layouts.app', ['title' => 'Remises PRO'])

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Conditions PRO</h1>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th>Règle</th>
                    <th>Catégorie</th>
                    <th>Remise</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rules as $rule)
                    <tr class="border-t border-slate-100">
                        <td>{{ $rule->name }}</td>
                        <td>{{ $rule->category?->name ?? 'Toutes catégories' }}</td>
                        <td>{{ $rule->discount_value }} %</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
