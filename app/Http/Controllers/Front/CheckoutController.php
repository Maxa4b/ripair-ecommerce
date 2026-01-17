<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\CheckoutAddressRequest;
use App\Http\Requests\Front\CheckoutIdentifyRequest;
use App\Http\Requests\Front\CheckoutPaymentRequest;
use App\Http\Requests\Front\CheckoutShippingRequest;
use App\Models\Commerce\Payment;
use App\Models\Commerce\Order;
use App\Services\Commerce\CartService;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\InvoiceService;
use App\Services\Logistics\ShippingService;
use App\Services\Payments\PayPalService;
use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkout,
        protected ShippingService $shipping,
        protected InvoiceService $invoices,
    ) {
    }

    public function index(Request $request)
    {
        $cart = $this->cartService->resolveCart($request->user())->load(['items.variant.product']);

        abort_if($cart->items->isEmpty(), 404, 'Panier vide');

        $forceResume = $request->boolean('resume');
        $checkoutData = $forceResume ? [] : session('checkout', []);
        if (empty($checkoutData)) {
            $checkoutData = data_get($cart->metadata, 'checkout', []);
            if (is_array($checkoutData) && ! empty($checkoutData)) {
                session()->put('checkout', $checkoutData);
            }
        }

        // Marque la commande comme "démarrée" dès l'entrée dans le checkout,
        // afin de pouvoir la reprendre plus tard depuis le compte.
        $metadata = is_array($cart->metadata) ? $cart->metadata : [];
        if (empty($metadata['checkout_started_at'])) {
            $metadata['checkout_started_at'] = now()->toISOString();
        }
        if (is_array($checkoutData) && ! empty($checkoutData)) {
            $metadata['checkout'] = $checkoutData;
            $metadata['checkout_updated_at'] = now()->toISOString();
        }
        $cart->update(['metadata' => $metadata]);

        $forcedShipping = $cart->forcedShippingFee();

        return view('front.checkout.index', [
            'cart' => $cart,
            // Les options Boxtal sont chargées en AJAX (UI non bloquante).
            'shippingOptions' => collect(),
            'checkoutData' => $checkoutData,
            'forcedShipping' => $forcedShipping,
        ]);
    }

    public function identify(CheckoutIdentifyRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        session()->put('checkout', array_merge(session('checkout', []), ['identity' => $data]));
        $this->persistCheckoutDraft($request);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'step' => 'identity']);
        }

        return back()->with('success', 'Adresse e-mail enregistrée.');
    }

    public function storeAddresses(CheckoutAddressRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        if ($request->boolean('billing_same', false)) {
            $data['billing'] = $data['shipping'] ?? [];
        }

        session()->put('checkout', array_merge(session('checkout', []), ['addresses' => $data]));
        $this->persistCheckoutDraft($request);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'step' => 'addresses',
                'addresses' => $data,
            ]);
        }

        return back()->with('success', 'Adresses enregistrées.');
    }

    public function selectShipping(CheckoutShippingRequest $request): RedirectResponse|JsonResponse
    {
        session()->put('checkout', array_merge(session('checkout', []), ['shipping' => $request->validated()]));
        $this->persistCheckoutDraft($request);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'step' => 'shipping']);
        }

        return back()->with('success', 'Mode de livraison sélectionné.');
    }

    public function pay(CheckoutPaymentRequest $request): RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->resolveCart($request->user())->load('items.variant.product', 'items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.show')->withErrors(['cart' => 'Votre panier est vide.']);
        }

        $checkoutData = session('checkout', []);
        $addresses = data_get($checkoutData, 'addresses');
        $shipping = data_get($checkoutData, 'shipping');

        if (! $addresses || ! $shipping) {
            return back()->withErrors(['checkout' => 'Merci de renseigner vos adresses et mode de livraison.']);
        }

        $user = $request->user();
        $customerEmail = $user?->email ?? data_get($checkoutData, 'identity.email');
        if (! $customerEmail) {
            return back()->withErrors(['checkout' => 'Merci de renseigner votre adresse e-mail.']);
        }

        $paymentPayload = $request->input('payment', []);
        if (($paymentPayload['method'] ?? null) === 'dev') {
            $paymentPayload['provider'] = 'dev';
        }

        $result = $this->checkout->createOrder($cart, $user, [
            'addresses' => $addresses,
            'shipping_method' => $shipping['shipping_method'],
            'notes' => $shipping['notes'] ?? null,
            'customer_email' => $customerEmail,
            'payment' => $paymentPayload,
        ]);
        $order = $result['order'];
        $paymentIntent = $result['payment_intent'] ?? [];

        session()->forget('checkout');
        session()->put('checkout.last_order', $order->id);
        $this->clearCheckoutDraft($cart);

        // Mode dev : saute le paiement et marque payé
        if (($paymentPayload['method'] ?? null) === 'dev') {
            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);
            if ($order->payments()->exists()) {
                $order->payments()->update(['status' => PaymentStatus::Paid->value, 'provider' => 'dev', 'method' => $paymentPayload['method']]);
            } else {
                \App\Models\Commerce\Payment::create([
                    'order_id' => $order->id,
                    'provider' => 'dev',
                    'method' => $paymentPayload['method'] ?? 'dev',
                    'amount' => $order->total_ttc,
                    'currency' => 'EUR',
                    'status' => PaymentStatus::Paid->value,
                    'transaction_reference' => 'dev-'.now()->timestamp,
                    'payload' => [],
                ]);
            }
            $invoice = $this->invoices->generate($order);
            $this->invoices->sendToCustomer($invoice);
            $order->refresh();
            $this->sendConfirmationEmail($order);
            $this->cartService->clearCart($cart);
            return redirect()->route('checkout.confirmation', $order)->with('success', 'Commande validée en mode dev (paiement sauté).');
        }

        // Si Stripe Checkout fourni une URL, on redirige le client vers Stripe
        if (($paymentIntent['provider'] ?? null) === 'stripe' && isset($paymentIntent['payload']['checkout_url'])) {
            return redirect()->away($paymentIntent['payload']['checkout_url']);
        }

        if (($paymentIntent['provider'] ?? null) === 'paypal') {
            if (isset($paymentIntent['payload']['approve_url'])) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'approve_url' => $paymentIntent['payload']['approve_url'],
                        'paypal_order_id' => $paymentIntent['reference'] ?? null,
                        'order_number' => $order->number,
                        'confirmation_url' => route('checkout.confirmation', $order),
                    ]);
                }
                return redirect()->away($paymentIntent['payload']['approve_url']);
            }
            $error = $paymentIntent['payload']['error'] ?? 'PayPal indisponible. Vérifiez la configuration (client_id/secret).';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $error], 422);
            }
            return back()->withErrors(['payment' => $error])->withInput();
        }

        return redirect()->route('checkout.confirmation', $order)->with('success', 'Commande enregistrée.');
    }

    public function paypalSuccess(Request $request)
    {
        $token = $request->string('token')->toString();
        $orderNumberParam = $request->string('order_number')->toString();
        if (! $token) {
            return redirect()->route('checkout.index')->withErrors(['payment' => 'Token PayPal manquant.']);
        }

        $paypal = new PayPalService();
        $capture = $paypal->capture($token);

        $payment = \App\Models\Commerce\Payment::where('provider', 'paypal')
            ->where('transaction_reference', $token)
            ->latest()
            ->first();

        $order = $payment?->order;

        // Fallbacks pour retrouver la commande meme si la reference n'a pas ete conservee
        $captureOrderId = data_get($capture, 'raw.purchase_units.0.custom_id');
        $captureInvoice = data_get($capture, 'raw.purchase_units.0.invoice_id');
        $captureDescription = data_get($capture, 'raw.purchase_units.0.description');

        if (! $order && $captureOrderId) {
            $order = Order::find($captureOrderId);
        }
        if (! $order && $captureInvoice) {
            $order = Order::where('number', $captureInvoice)->latest()->first();
        }
        if (! $order && $orderNumberParam) {
            $order = Order::where('number', $orderNumberParam)->latest()->first();
        }
        if (! $order && $captureDescription && str_contains($captureDescription, 'Commande ')) {
            $number = trim(str_replace('Commande', '', $captureDescription));
            $order = Order::where('number', $number)->latest()->first();
        }
        if (! $order && session('checkout.last_order')) {
            $order = Order::find(session('checkout.last_order'));
        }

        if (! $payment && $order) {
            // Cree le paiement si jamais il n'a pas ete enregistre
            $payment = $order->payments()->where('provider', 'paypal')->latest()->first();
            if (! $payment) {
                $payment = \App\Models\Commerce\Payment::create([
                    'order_id' => $order->id,
                    'provider' => 'paypal',
                    'method' => 'paypal',
                    'amount' => $order->total_ttc,
                    'currency' => 'EUR',
                    'status' => PaymentStatus::Pending->value,
                    'transaction_reference' => $token,
                    'payload' => [],
                ]);
            }
        }

        if (! $payment || ! $order) {
            return redirect()->route('checkout.index')->withErrors(['payment' => 'Paiement PayPal introuvable.']);
        }

        $captureStatus = $capture['status'] ?? '';
        $isPaid = in_array(strtolower($captureStatus), ['paid', 'completed', 'approved']);

        $payment->update([
            'status' => $isPaid ? PaymentStatus::Paid->value : PaymentStatus::Pending->value,
            'transaction_reference' => $payment->transaction_reference ?? $token,
            'payload' => array_merge($payment->payload ?? [], ['capture' => $capture]),
        ]);

        if ($order && $isPaid) {
            // Autorise l'affichage de la page de confirmation pour les invits qui reviennent de PayPal
            session()->put('checkout.last_order', $order->id);
            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);
            $invoice = $this->invoices->generate($order);
            $this->invoices->sendToCustomer($invoice);
            $order->refresh();
            $this->sendConfirmationEmail($order);
            if ($order->cart) {
                $this->cartService->clearCart($order->cart);
            }
        }

        return redirect()->route('checkout.confirmation', $order)->with('success', 'Paiement PayPal confirm.');
    }

public function paypalCancel()
    {
        return redirect()->route('checkout.index')->withErrors(['payment' => 'Paiement PayPal annulé.']);
    }

    public function shippingOptionsAjax(Request $request)
    {
        $checkoutData = session('checkout', []);

        $cart = $this->cartService->resolveCart($request->user())->loadMissing(['items', 'promoCode']);
        $forcedShipping = $cart->forcedShippingFee();
        $freeShippingMin = (float) (config('pricing.free_shipping_min_total') ?? 0);
        $shippingSurcharge = (float) (config('pricing.shipping_surcharge') ?? 0);
        $eligibleBase = max(0, (float) ($cart->subtotal_ttc ?? 0));
        $hasShippingPromo = $cart->promoCode && $cart->promoCode->discount_type === 'shipping';
        $isFreeShipping = $shippingSurcharge <= 0
            && ! $forcedShipping
            && ($hasShippingPromo || ($freeShippingMin > 0 && $eligibleBase >= $freeShippingMin));

        // Checkout : options fixes (sans appel à l'API Boxtal). L'intégration Boxtal est conservée
        // et sera réutilisée ailleurs, mais on déconnecte cette page du réseau externe.
        if ($forcedShipping) {
            $shippingOptions = collect([
                [
                    'method_id' => 'manual:picofly_delivery',
                    'name' => 'Livraison Picofly',
                    'price' => round((float) $forcedShipping + $shippingSurcharge, 2),
                    'price_original' => round((float) $forcedShipping + $shippingSurcharge, 2),
                    'delay' => null,
                    'type' => 'shipping',
                    'origin' => 'forced_picofly',
                    'provider' => 'manual',
                    'operator' => 'RIPAIR',
                    'is_free' => false,
                ],
            ]);
        } else {
            $shippingOptions = collect([
            [
                'method_id' => 'manual:colissimo_home_no_sig',
                'name' => 'La Poste Colissimo Domicile - Sans Signature',
                'price' => 7.90,
                'delay' => '22/12/2025',
                'type' => 'shipping',
                'origin' => 'static_checkout',
                'provider' => 'manual',
                'operator' => 'COLI',
            ],
            [
                'method_id' => 'manual:colissimo_home_sig',
                'name' => 'La Poste Colissimo Domicile - Avec Signature',
                'price' => 8.90,
                'delay' => '22/12/2025',
                'type' => 'shipping',
                'origin' => 'static_checkout',
                'provider' => 'manual',
                'operator' => 'COLI',
            ],
            [
                'method_id' => 'manual:chronopost_shop2shop_relay',
                'name' => 'Chronopost Shop2Shop - Point relais',
                'price' => 3.90,
                'delay' => '22/12/2025',
                'type' => 'relay',
                'origin' => 'static_checkout',
                'provider' => 'manual',
                'operator' => 'CHRP',
            ],
            [
                'method_id' => 'manual:mondial_relay',
                'name' => 'Mondial Relay - Point relais',
                'price' => 3.90,
                'delay' => '25/12/2025',
                'type' => 'relay',
                'origin' => 'static_checkout',
                'provider' => 'manual',
                'operator' => 'MONR',
            ],
        ])->map(function ($opt) use ($isFreeShipping, $shippingSurcharge) {
            $base = (float) ($opt['price'] ?? 0);
            $opt['price_original'] = round($base + $shippingSurcharge, 2);
            $opt['price'] = round($base + $shippingSurcharge, 2);
            $opt['is_free'] = ($isFreeShipping && $base > 0);
            if ($isFreeShipping && $base > 0) {
                $name = (string) ($opt['name'] ?? 'Livraison');
                $opt['name'] = str_contains(mb_strtolower($name), 'offerte') ? $name : $name.' (offerte)';
                $opt['price'] = 0.0;
            }
            return $opt;
        });
        }

        session()->put('boxtal_quotes', $shippingOptions->keyBy('method_id')->toArray());
        session()->put('boxtal_quotes_key', $forcedShipping ? 'forced_picofly' : 'static_checkout');
        session()->put('boxtal_quotes_cached_at', time());

        $selectedShippingId = data_get($checkoutData, 'shipping.shipping_method') ?? $shippingOptions->first()['method_id'];

        $html = view('front.checkout.partials.shipping-options', [
            'shippingOptions' => $shippingOptions,
            'checkoutData' => $checkoutData,
            'selectedShippingId' => $selectedShippingId,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function confirmation(Order $order)
    {
        $order->load('items', 'payments', 'shippingMethod');

        // Si retour Stripe avec session_id et paiement encore en attente, on valide côté Stripe
        if ($order->payment_status === PaymentStatus::Pending && request()->filled('session_id') && config('services.stripe.secret')) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $session = $stripe->checkout->sessions->retrieve(request('session_id'));
                if (($session->payment_status ?? '') === 'paid') {
                    $order->update([
                        'payment_status' => PaymentStatus::Paid,
                        'status' => OrderStatus::Paid,
                    ]);
                    $payment = $order->payments()->where('transaction_reference', $session->id)->latest()->first();
                    if ($payment) {
                        $payment->update(['status' => PaymentStatus::Paid->value, 'payload' => array_merge($payment->payload ?? [], ['session' => $session])]);
                    }
                    $invoice = $this->invoices->generate($order);
                    $this->invoices->sendToCustomer($invoice);
                    $order->refresh();
                    $this->sendConfirmationEmail($order);
                    if ($order->cart) {
                        $this->cartService->clearCart($order->cart);
                    }
                }
            } catch (\Throwable $e) {
                // on ne bloque pas l'affichage, juste log si besoin
                logger()->warning('Stripe session check failed', ['error' => $e->getMessage()]);
            }
        }

        $allowed = false;
        if (auth()->check()) {
            $this->authorize('view', $order);
            $allowed = true;
        }

        if (! $allowed && session('checkout.last_order') === $order->id) {
            $allowed = true;
        }

        // Autorise l'accès invité si le session_id Stripe de retour correspond au paiement de la commande
        if (! $allowed && request()->filled('session_id')) {
            $sessionId = request()->string('session_id')->toString();
            $allowed = $order->payments()
                ->where('provider', 'stripe')
                ->where('transaction_reference', $sessionId)
                ->exists();
        }

        if (! $allowed) {
            abort(403);
        }

        $view = view('front.checkout.confirmation', [
            'order' => $order,
        ]);

        session()->forget('checkout.last_order');

        return $view;
    }

    private function sendConfirmationEmail(Order $order): void
    {
        $metadata = $order->metadata ?? [];
        // Si déjà envoyé via la facture (invite ou compte), on ne renvoie pas.
        if (data_get($metadata, 'confirmation_sent') || data_get($metadata, 'invoice_sent')) {
            return;
        }

        // Email saisi dans le checkout (compte ou invité)
        $to = $order->user?->email ?? data_get($order->billing_address, 'email');
        if (! $to) {
            return;
        }

        $subject = 'Confirmation de commande '.$order->number;
        $total = number_format($order->total_ttc, 2, ',', ' ');
        $statusLabel = $order->status?->label() ?? 'Payée';
        $html = view('emails.order_confirmation', [
            'order' => $order,
            'total' => $total,
            'statusLabel' => $statusLabel,
        ])->render();
        $text = "Merci pour votre commande {$order->number}.\nMontant TTC : {$total} €\nStatut : {$statusLabel}\n\nNous préparons votre commande et vous informerons dès l’expédition.";

        try {
            Mail::send([], [], function ($mail) use ($to, $subject, $html, $text) {
                $mail->to($to)->subject($subject);
                $mail->html($html);
                $mail->text($text);
            });
            $order->update(['metadata' => array_merge($metadata, ['confirmation_sent' => true])]);
        } catch (\Throwable $e) {
            logger()->warning('email_confirmation_failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    private function persistCheckoutDraft(Request $request): void
    {
        try {
            $cart = $this->cartService->resolveCart($request->user())->loadMissing('items');
            if ($cart->items->isEmpty()) {
                return;
            }

            $checkoutData = session('checkout', []);
            if (! is_array($checkoutData) || empty($checkoutData)) {
                return;
            }

            $metadata = is_array($cart->metadata) ? $cart->metadata : [];
            if (empty($metadata['checkout_started_at'])) {
                $metadata['checkout_started_at'] = now()->toISOString();
            }
            $metadata['checkout'] = $checkoutData;
            $metadata['checkout_updated_at'] = now()->toISOString();

            $cart->update(['metadata' => $metadata]);
        } catch (\Throwable $e) {
            // On ne bloque jamais le checkout sur une erreur de sauvegarde.
            logger()->warning('checkout_draft_persist_failed', ['error' => $e->getMessage()]);
        }
    }

    private function clearCheckoutDraft(\App\Models\Commerce\Cart $cart): void
    {
        $metadata = is_array($cart->metadata) ? $cart->metadata : [];
        unset($metadata['checkout'], $metadata['checkout_updated_at'], $metadata['checkout_started_at']);
        $cart->update(['metadata' => empty($metadata) ? null : $metadata]);
    }

}
