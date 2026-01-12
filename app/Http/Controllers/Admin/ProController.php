<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProController extends Controller
{
    public function index()
    {
        return view('admin.pro.index', [
            'pending' => User::where('pro_status', 'pending')->get(),
            'approved' => User::where('pro_status', 'approved')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, User $pro): RedirectResponse
    {
        $request->validate([
            'pro_status' => ['required', 'in:approved,rejected'],
            'pro_discount_rate' => ['nullable', 'numeric', 'min:0', 'max:40'],
        ]);

        $status = $request->string('pro_status')->toString();

        $pro->update([
            'pro_status' => $status,
            'pro_discount_rate' => $request->input('pro_discount_rate', $pro->pro_discount_rate),
            'can_access_ht_prices' => $status === 'approved',
            'pro_validated_at' => now(),
        ]);

        return back()->with('success', 'Compte PRO mis à jour.');
    }
}
