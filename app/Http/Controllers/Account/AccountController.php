<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\Commerce\Cart;
use App\Models\Commerce\Invoice;
use App\Models\Support\ProPricingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard(Request $request)
    {
        $openOrders = 0;
        $draftCheckoutCount = 0;
        $rmaCount = 0;
        $addresses = collect();
        $invoicesCount = 0;

        // Evite de planter si certaines tables ne sont pas encore créées.
        if (Schema::hasTable('orders')) {
            $openOrders = $request->user()->orders()->whereNot('status', 'delivered')->count();
            if (Schema::hasTable('invoices')) {
                $invoicesCount = Invoice::query()
                    ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
                    ->count();
            }
        }
        if (Schema::hasTable('rma_requests')) {
            $rmaCount = $request->user()->rmaRequests()->count();
        }
        if (Schema::hasTable('addresses')) {
            $addresses = $request->user()->addresses;
        }

        // Commande en cours (checkout non finalisé) : stockée dans le panier (metadata.checkout_started_at / metadata.checkout).
        if (Schema::hasTable('carts')) {
            $draftCart = $request->user()->cart()->withCount('items')->first();

            if (! $draftCart && session()->has('cart_token')) {
                $tokenCart = Cart::where('token', session('cart_token'))->withCount('items')->first();
                if ($tokenCart && (! $tokenCart->user_id || (int) $tokenCart->user_id === (int) $request->user()->id)) {
                    $draftCart = $tokenCart;
                }
            }

            $draftCheckout = data_get($draftCart?->metadata, 'checkout');
            $checkoutStartedAt = data_get($draftCart?->metadata, 'checkout_started_at');

            $hasDraft = $draftCart
                && $draftCart->status === 'open'
                && (int) ($draftCart->items_count ?? 0) > 0
                && ((is_array($draftCheckout) && ! empty($draftCheckout)) || filled($checkoutStartedAt));

            $draftCheckoutCount = $hasDraft ? 1 : 0;
        }

        return view('account.dashboard', [
            'user' => $request->user()->load(['orders' => fn ($q) => $q->latest('placed_at')->limit(5)]),
            'openOrders' => $openOrders,
            'draftCheckoutCount' => $draftCheckoutCount,
            'rmaCount' => $rmaCount,
            'addresses' => $addresses,
            'invoicesCount' => $invoicesCount,
        ]);
    }

    public function profile(Request $request)
    {
        return redirect()->route('account.dashboard');
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $request->user()->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function proDashboard(Request $request)
    {
        abort_unless($request->user()->isPro(), 403);

        return view('account.pro.dashboard', [
            'user' => $request->user()->load('orders'),
            'metrics' => [
                'orders' => $request->user()->orders()->count(),
                'total_spend' => $request->user()->orders()->sum('total_ttc'),
            ],
        ]);
    }

    public function pricing(Request $request)
    {
        abort_unless($request->user()->isPro(), 403);

        return view('account.pro.pricing', [
            'rules' => ProPricingRule::query()->where('is_active', true)->get(),
        ]);
    }
}
