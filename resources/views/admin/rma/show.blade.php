@extends('layouts.admin', ['title' => "RMA {$rma->rma_number}"])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Description</h2>
            <p class="mt-2 text-sm text-slate-600">{{ $rma->description }}</p>
            <h3 class="mt-6 text-sm font-semibold text-slate-500">Commentaires</h3>
            <ul class="mt-2 space-y-2 text-sm text-slate-600">
                @foreach ($rma->comments as $comment)
                    <li class="rounded border border-slate-100 px-3 py-2">{{ $comment->comment }}</li>
                @endforeach
            </ul>
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Statut</h2>
            <form method="POST" action="{{ route('admin.sav-rma.update', $rma) }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')
                <select name="status" class="w-full rounded border-slate-200">
                    @foreach (\App\Enums\RmaStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($status === $rma->status)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
                <textarea name="comment" placeholder="Note interne" class="w-full rounded border-slate-200"></textarea>
                <button class="w-full rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Mettre à jour</button>
            </form>
        </section>
    </div>
@endsection
