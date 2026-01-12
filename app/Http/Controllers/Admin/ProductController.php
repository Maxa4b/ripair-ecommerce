<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductType;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index', [
            'products' => Product::query()->with(['brand', 'category'])->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'types' => ProductType::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()->route('admin.catalogue-produits.edit', $product)->with('success', 'Produit créé.');
    }

    public function edit(Product $produit)
    {
        return view('admin.products.form', [
            'product' => $produit,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'types' => ProductType::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $produit): RedirectResponse
    {
        $produit->update($request->validated());

        return back()->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $produit): RedirectResponse
    {
        $produit->delete();

        return redirect()->route('admin.catalogue-produits.index')->with('success', 'Produit supprimé.');
    }
}
