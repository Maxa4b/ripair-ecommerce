<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Models\Support\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return view('account.addresses.index', [
            // On n'affiche qu'une adresse par type pour éviter les doublons visuels.
            'addresses' => $request->user()->addresses->unique('type')->values(),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Crée l'adresse sélectionnée
        $request->user()->addresses()->create($data);

        // Si l'utilisateur veut la même adresse pour la facturation, on duplique.
        if ($request->boolean('duplicate_billing') && $data['type'] === 'shipping') {
            $billing = $data;
            $billing['type'] = 'billing';
            $existingBilling = $request->user()->addresses()->where('type', 'billing')->first();
            if ($existingBilling) {
                $existingBilling->update($billing);
            } else {
                $request->user()->addresses()->create($billing);
            }
        }

        return back()->with('success', 'Adresse ajoutée.');
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $address->update($request->validated());

        return back()->with('success', 'Adresse mise à jour.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $address->delete();

        return back()->with('success', 'Adresse supprimée.');
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_if($address->user_id !== $request->user()->id, 403);
    }
}
