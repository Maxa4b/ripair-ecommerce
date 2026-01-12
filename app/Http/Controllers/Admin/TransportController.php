<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransportRequest;
use App\Models\Logistics\ShippingMethod;
use App\Models\Logistics\Transporter;
use Illuminate\Http\RedirectResponse;

class TransportController extends Controller
{
    public function index()
    {
        return view('admin.transport.index', [
            'methods' => ShippingMethod::with('transporter', 'rates')->get(),
            'transporters' => Transporter::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.transport.form', [
            'method' => new ShippingMethod(),
            'transporters' => Transporter::orderBy('name')->get(),
        ]);
    }

    public function store(TransportRequest $request): RedirectResponse
    {
        ShippingMethod::create($request->validated());

        return redirect()->route('admin.transporteurs.index')->with('success', 'Mode de livraison créé.');
    }

    public function edit(ShippingMethod $transporteur)
    {
        return view('admin.transport.form', [
            'method' => $transporteur,
            'transporters' => Transporter::orderBy('name')->get(),
        ]);
    }

    public function update(TransportRequest $request, ShippingMethod $transporteur): RedirectResponse
    {
        $transporteur->update($request->validated());

        return back()->with('success', 'Mode de livraison mis à jour.');
    }

    public function destroy(ShippingMethod $transporteur): RedirectResponse
    {
        $transporteur->delete();

        return redirect()->route('admin.transporteurs.index')->with('success', 'Mode de livraison supprimé.');
    }
}
