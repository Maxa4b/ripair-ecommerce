@extends('layouts.admin', ['title' => $product->exists ? 'Modifier '.$product->name : 'Créer un produit'])

@section('content')
    <form method="POST" action="{{ $product->exists ? route('admin.catalogue-produits.update', $product) : route('admin.catalogue-produits.store') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif
        <div class="grid gap-4 md:grid-cols-3">
            <input name="name" value="{{ old('name', $product->name) }}" class="rounded border-slate-200" placeholder="Nom">
            <input name="slug" value="{{ old('slug', $product->slug) }}" class="rounded border-slate-200" placeholder="Slug">
            <select name="category_id" class="rounded border-slate-200">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($category->id == $product->category_id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="brand_id" class="rounded border-slate-200">
                <option value="">Sans marque</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected($brand->id == $product->brand_id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <select name="product_type_id" class="rounded border-slate-200">
                <option value="">Type</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected($type->id == $product->product_type_id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <input name="internal_reference" value="{{ old('internal_reference', $product->internal_reference) }}" class="rounded border-slate-200" placeholder="Référence interne">
            <input name="supplier_reference" value="{{ old('supplier_reference', $product->supplier_reference) }}" class="rounded border-slate-200" placeholder="Référence fournisseur">
        </div>
        <textarea name="short_description" class="w-full rounded border-slate-200" placeholder="Résumé">{{ old('short_description', $product->short_description) }}</textarea>
        <textarea name="description" class="w-full rounded border-slate-200" rows="6" placeholder="Description détaillée">{{ old('description', $product->description) }}</textarea>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_published" value="1" @checked($product->is_published)> Publié
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_best_seller" value="1" @checked($product->is_best_seller)> Best seller
            </label>
        </div>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
    </form>
@endsection
