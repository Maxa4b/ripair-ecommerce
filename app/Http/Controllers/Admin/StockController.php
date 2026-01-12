<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockMovementRequest;
use App\Models\Catalog\InventoryMovement;
use App\Models\Catalog\ProductVariant;
use Illuminate\Http\RedirectResponse;

class StockController extends Controller
{
    public function index()
    {
        return view('admin.stock.index', [
            'movements' => InventoryMovement::query()->with('variant.product')->latest()->paginate(30),
        ]);
    }

    public function store(StockMovementRequest $request): RedirectResponse
    {
        $variant = ProductVariant::findOrFail($request->integer('product_variant_id'));
        $movement = InventoryMovement::create(array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id],
        ));

        $quantity = abs($movement->quantity);
        if ($movement->type === 'out') {
            $variant->decrement('stock_on_hand', $quantity);
        } else {
            $variant->increment('stock_on_hand', $quantity);
        }

        return back()->with('success', 'Mouvement enregistré.');
    }
}
