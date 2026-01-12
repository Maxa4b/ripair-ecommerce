@extends('layouts.admin', ['title' => 'Catalogue'])

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.catalogue-produits.create') }}" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nouveau produit</a>
    </div>
    <table class="w-full rounded-2xl bg-white text-sm shadow-sm">
        <thead>
            <tr class="text-left text-slate-500">
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr class="border-t border-slate-100">
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name }}</td>
                    <td>{{ $product->is_published ? 'Publié' : 'Brouillon' }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.catalogue-produits.edit', $product) }}" class="text-indigo-600">Modifier</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
