@extends('layouts.admin', ['title' => 'Pages légales'])

@section('content')
    <form method="POST" action="{{ route('admin.content.pages.update') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        <select name="key" class="w-full rounded border-slate-200">
            <option value="legal_mentions">Mentions légales</option>
            <option value="cgv">CGV</option>
            <option value="privacy_policy">Confidentialité</option>
            <option value="cookies">Cookies</option>
        </select>
        <textarea name="value" rows="8" class="w-full rounded border-slate-200" placeholder="Contenu HTML">{!! old('value', data_get($pages, 'legal_mentions.value.html')) !!}</textarea>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Mettre à jour</button>
    </form>
@endsection
