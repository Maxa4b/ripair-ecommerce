<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Commerce\Order;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function index()
    {
        return view('admin.orders.index', [
            'orders' => Order::query()->with('user')->latest('placed_at')->paginate(25),
        ]);
    }

    public function show(Order $commande)
    {
        return view('admin.orders.show', [
            'order' => $commande->load('items.variant', 'payments', 'shipments'),
        ]);
    }

    public function update(OrderStatusRequest $request, Order $commande): RedirectResponse
    {
        $oldStatus = $commande->status?->value;

        $commande->update($request->validated());

        $commande->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $request->input('status'),
            'user_id' => $request->user()->id,
            'comment' => $request->input('internal_note'),
        ]);

        return back()->with('success', 'Commande mise à jour.');
    }
}
