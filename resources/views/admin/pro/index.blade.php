@extends('layouts.admin', ['title' => 'Comptes PRO'])

@section('content')
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Demandes en attente</h2>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th>Client</th>
                    <th>SIRET</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending as $user)
                    <tr class="border-t border-slate-100">
                        <td>{{ $user->company_name }}</td>
                        <td>{{ $user->siret }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.pro.update', $user) }}" class="inline-flex gap-2">
                                @csrf
                                @method('PUT')
                                <select name="pro_status" class="rounded border-slate-200 text-sm">
                                    <option value="approved">Approuver</option>
                                    <option value="rejected">Refuser</option>
                                </select>
                                <input name="pro_discount_rate" value="{{ $user->pro_discount_rate }}" class="w-20 rounded border-slate-200" placeholder="%">
                                <button class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">Valider</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
