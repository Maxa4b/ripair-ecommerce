@extends('layouts.admin', ['title' => $method->exists ? 'Modifier un mode' : 'Créer un mode'])

@section('content')
    <form method="POST" action="{{ $method->exists ? route('admin.transporteurs.update', $method) : route('admin.transporteurs.store') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        @if ($method->exists)
            @method('PUT')
        @endif
        <select name="transporter_id" class="w-full rounded border-slate-200">
            <option value="">Transporteur</option>
            @foreach ($transporters as $transporter)
                <option value="{{ $transporter->id }}" @selected($transporter->id == $method->transporter_id)>{{ $transporter->name }}</option>
            @endforeach
        </select>
        <input name="name" value="{{ old('name', $method->name) }}" placeholder="Nom" class="w-full rounded border-slate-200">
        <input name="code" value="{{ old('code', $method->code) }}" placeholder="Code" class="w-full rounded border-slate-200">
        <select name="type" class="w-full rounded border-slate-200">
            <option value="home">Domicile</option>
            <option value="relay">Point relais</option>
            <option value="workshop_pickup">Retrait atelier</option>
        </select>
        <input name="base_price" value="{{ old('base_price', $method->base_price) }}" placeholder="Prix base" class="w-full rounded border-slate-200">
        <div class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="supports_tracking" value="1" @checked($method->supports_tracking)> Tracking
        </div>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
    </form>
@endsection
