<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Commerce\Cart;
use App\Models\Commerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with('invoice')
            ->latest('placed_at')
            ->paginate(10);

        $draftCart = $request->user()->cart()->withCount('items')->first();

        // Fallback : si le panier n'a pas été rattaché (session en cours), on utilise le token de session.
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
            && (int) $draftCart->items_count > 0
            && ((is_array($draftCheckout) && ! empty($draftCheckout)) || filled($checkoutStartedAt));

        $draftStep = 1;
        if ($hasDraft && is_array($draftCheckout) && ! empty($draftCheckout)) {
            $addressesDone = filled(data_get($draftCheckout, 'addresses.billing.first_name'))
                && filled(data_get($draftCheckout, 'addresses.shipping.first_name'));
            $shippingDone = filled(data_get($draftCheckout, 'shipping.shipping_method'));

            if ($addressesDone) {
                $draftStep = 3;
            }
            if ($shippingDone) {
                $draftStep = 4;
            }
            if (! $addressesDone && filled(data_get($draftCheckout, 'identity.email'))) {
                $draftStep = 2;
            }
        }

        return view('account.orders.index', compact('orders', 'hasDraft', 'draftCart', 'draftStep'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $relations = ['items', 'payments', 'invoice'];
        if (Schema::hasTable('shipments')) {
            $relations[] = 'shipments';
        }

        return view('account.orders.show', [
            'order' => $order->load($relations),
        ]);
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->isPro(), 403);

        $orders = $request->user()->orders()->latest('placed_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="commandes.csv"',
        ];

        $callback = static function () use ($orders): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Numéro', 'Date', 'Statut', 'Total TTC']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->number,
                    $order->placed_at,
                    $order->status->value,
                    $order->total_ttc,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'commandes.csv', $headers);
    }
}
