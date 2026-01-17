@extends('layouts.app', ['title' => 'Commande'])

@section('content')
    <style>
        .shipping-card-grid {display:grid; gap:12px;}
        .shipping-card-modern {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 16px; border:1px solid #e5e7eb; border-radius:14px;
            background:#fff; box-shadow:0 8px 20px -12px rgba(0,0,0,0.18);
            transition:all .2s ease; cursor:pointer;
        }
        .shipping-card-modern:hover {border-color:#7cc4ff; box-shadow:0 12px 26px -14px rgba(0,123,255,0.28);}
        .shipping-card-modern .left {display:flex; gap:12px; align-items:center;}
        .ship-name {font-weight:700; color:#0b1f4f;}
        .ship-delay {font-size:13px; color:#4b5563; margin-top:2px;}
        .ship-price {font-weight:800; color:#0b1f4f; font-size:18px;}
        .ship-radio {display:none;}
        .ship-radio:checked + .shipping-card-modern {border-color:#0ea5e9; box-shadow:0 14px 28px -14px rgba(14,165,233,0.5); background:linear-gradient(135deg,#f8fbff 0%,#ffffff 80%);}
        .ship-logo {width:52px; height:52px; border-radius:12px; object-fit:contain; background:#fff; box-shadow:0 4px 12px -8px rgba(0,0,0,0.25); padding:6px;}
        .pay-grid {display:grid; gap:12px;}
        .payment-card-modern {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 16px; border:1px solid #e5e7eb; border-radius:14px;
            background:#fff; box-shadow:0 8px 20px -12px rgba(0,0,0,0.18);
            transition:all .2s ease; cursor:pointer;
        }
        .payment-card-modern:hover {border-color:#7cc4ff; box-shadow:0 12px 26px -14px rgba(0,123,255,0.28);}
        .payment-card-modern .left {display:flex; gap:12px; align-items:center;}
        .pay-logo {
            width:64px;
            height:34px;
            border-radius:12px;
            object-fit:contain;
            background:#fff;
            box-shadow:0 4px 12px -8px rgba(0,0,0,0.25);
            padding:0;
            display:block;
        }
        .pay-radio {display:none;}
        .pay-radio:checked + .payment-card-modern {border-color:#0ea5e9; box-shadow:0 14px 28px -14px rgba(14,165,233,0.5); background:linear-gradient(135deg,#f8fbff 0%,#ffffff 80%);}
        .card-pill-row {display:flex; gap:8px; margin-top:6px;}
        .card-pill {
            background:#f1f5f9;
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:4px 10px;
            display:flex;
            align-items:center;
            justify-content:center;
            min-width:64px;
            min-height:28px;
            overflow:hidden;
        }
        .card-pill img {max-height:16px; max-width:60px; display:block; object-fit:contain;}
        .card-pill img.gpay-zoom {
            max-height:none;
            max-width:none;
            height:26px;
            width:auto;
            object-fit:contain;
            clip-path: inset(20% 0 20% 0);
            background: transparent;
            mix-blend-mode:multiply;
            filter: brightness(1.05);
        }
    </style>
    <section class="checkout-layout">
        @php
            $freeShippingMin = config('pricing.free_shipping_min_total');
            $smallOrderFee = config('pricing.small_order_shipping_fee');
            $shippingSurcharge = config('pricing.shipping_surcharge', 0);
            $forcedShipping = $forcedShipping ?? null;
            // Eligibilité calculée sur le panier avant remise pour ne pas annuler la livraison offerte
            $eligibleBase = max(0, $cart->subtotal_ttc);
            $hasShippingPromo = $cart->promoCode && $cart->promoCode->discount_type === 'shipping';
            $allowFreeShipping = $shippingSurcharge <= 0;
            $isFreeShippingEligible = $allowFreeShipping
                && ! $forcedShipping
                && ($hasShippingPromo || ($freeShippingMin ? $eligibleBase >= $freeShippingMin : false));
            $remainingForFree = $isFreeShippingEligible
                ? 0
                : ($allowFreeShipping && $freeShippingMin ? max(0, $freeShippingMin - $eligibleBase) : 0);
            $selectedShippingId = old('shipping_method', data_get($checkoutData, 'shipping.shipping_method'));
            $selectedShipping = collect($shippingOptions)->firstWhere('method_id', $selectedShippingId);
            $selectedShippingPrice = $selectedShipping['price'] ?? null;
            if ($isFreeShippingEligible) {
                $selectedShippingPrice = 0;
            }
            $shippingDisplay = $selectedShippingPrice ?? 0;
            $shippingSelected = filled($selectedShippingId);
            $summaryBase = $cart->subtotal_ttc - $cart->discount_total;
            $summaryTotal = $summaryBase + ($shippingSelected ? $shippingDisplay : 0);
            $identityDone = filled(data_get($checkoutData, 'identity.email')) || auth()->check();
            $addressesDone = filled(data_get($checkoutData, 'addresses.billing.first_name')) && filled(data_get($checkoutData, 'addresses.shipping.first_name'));
            $shippingDone = filled($selectedShippingId);
            $shippingAddress = data_get($checkoutData, 'addresses.shipping', []);
            $activeStep = 1;
            if ($identityDone) { $activeStep = 2; }
            if ($addressesDone) { $activeStep = 3; }
            if ($shippingDone) { $activeStep = 4; }
            $homeOptions = collect($shippingOptions)->filter(fn($opt) => ! str_contains(strtolower($opt['name']), 'relay') && ! str_contains(strtolower($opt['name']), 'relais'));
            $relayOptions = collect($shippingOptions)->filter(fn($opt) => str_contains(strtolower($opt['name']), 'relay') || str_contains(strtolower($opt['name']), 'relais'));
            if ($homeOptions->isEmpty() && $relayOptions->isEmpty()) {
                $homeOptions = collect($shippingOptions);
            }
            $hasHome = $homeOptions->isNotEmpty();
            $hasRelay = $relayOptions->isNotEmpty();
            $user = auth()->user();
            $defaultShipping = $user?->addresses()->where('type', 'shipping')->orderByDesc('is_default_shipping')->first();
            $defaultBilling = $user?->addresses()->where('type', 'billing')->orderByDesc('is_default_billing')->first();
        @endphp
        <div class="checkout-steps" data-stepper data-active-step="{{ $activeStep }}">
            <header class="checkout-head">
                <h1>Finalisez votre commande</h1>
                <div class="checkout-progress" data-max-step="{{ $shippingDone ? 4 : ($addressesDone ? 3 : ($identityDone ? 2 : 1)) }}">
                    <button type="button" class="dot @if($activeStep === 1) is-active @endif @if($identityDone) is-complete @endif" data-step-target="1" aria-label="Étape 1">1</button>
                    <button type="button" class="dot @if($activeStep === 2) is-active @endif @if($addressesDone) is-complete @endif" data-step-target="2" aria-label="Étape 2">2</button>
                    <button type="button" class="dot @if($activeStep === 3) is-active @endif @if($shippingDone) is-complete @endif" data-step-target="3" aria-label="Étape 3">3</button>
                    <button type="button" class="dot @if($activeStep === 4) is-active @endif" data-step-target="4" aria-label="Étape 4">4</button>
                </div>
            </header>

            <div class="step-panels">
            <div class="checkout-step" data-step-index="1">
                <div class="step-head">
                    <span class="step-index">01</span>
                    <div>
                        <h3>Identification</h3>
                        <p>Renseignez votre email ou connectez-vous.</p>
                    </div>
                </div>
                <div class="step-alert step-alert--error is-hidden" data-step-errors="1"></div>
                @if($errors->has('email') || $errors->has('mode'))
                    <div class="step-alert step-alert--error">
                        <strong>Vérifiez vos informations :</strong>
                        <ul>
                            @php $msgs = array_merge($errors->get('email'), $errors->get('mode')); @endphp
                            @foreach($msgs as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('checkout.identify') }}" class="step-body" data-step-next="2">
                    @csrf
                    <div class="field-grid">
                        @if(auth()->check())
                            <div class="login-inline">
                                <div>
                                    <p class="login-hint" style="margin:0;font-weight:700;color:#0b1f4f;">Connecté en tant que</p>
                                    <p class="login-hint" style="margin:2px 0 8px;">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-secondary">Se déconnecter</a>
                            </div>
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                        @else
                            <input type="email" name="email" placeholder="email@entreprise.fr" value="{{ old('email', data_get($checkoutData, 'identity.email')) }}" class="catalog-input" required>
                            <div class="login-inline">
                                <a href="{{ route('login') }}?redirect=/checkout" class="btn btn-secondary">Se connecter</a>
                                <p class="login-hint">En vous connectant, vous récupérez vos adresses et suivez vos commandes.</p>
                            </div>
                        @endif
                    </div>
                    <button class="btn step-cta">Continuer</button>
                </form>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
            </div>

            <div class="checkout-step" data-step-index="2">
                <div class="step-head">
                    <span class="step-index">02</span>
                    <div>
                        <h3>Adresses</h3>
                        <p>Facturation et livraison.</p>
                    </div>
                </div>
                <div class="step-alert step-alert--error is-hidden" data-step-errors="2"></div>
                @if($errors->hasBag('default') && collect($errors->keys())->contains(fn($k) => str_starts_with($k, 'billing.') || str_starts_with($k, 'shipping.')))
                    <div class="step-alert step-alert--error">
                        <strong>Complétez vos adresses :</strong>
                        <ul>
                            @foreach($errors->all() as $msg)
                                @if(str_contains($msg, 'billing') || str_contains($msg, 'shipping'))
                                    <li>{{ $msg }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('checkout.addresses') }}" class="step-body" data-step-next="3">
                    @csrf
                    <div class="field-grid two-cols">
                        <input name="shipping[first_name]" placeholder="Prénom" class="catalog-input" value="{{ old('shipping.first_name', data_get($checkoutData, 'addresses.shipping.first_name', $defaultShipping?->first_name ?? auth()->user()?->first_name)) }}">
                        <input name="shipping[last_name]" placeholder="Nom" class="catalog-input" value="{{ old('shipping.last_name', data_get($checkoutData, 'addresses.shipping.last_name', $defaultShipping?->last_name ?? auth()->user()?->last_name)) }}">
                        <div class="address-autocomplete">
                            <input name="shipping[line1]" placeholder="Adresse" class="catalog-input" autocomplete="new-password" data-lpignore="true" data-form-type="other" autocapitalize="off" spellcheck="false" value="{{ old('shipping.line1', data_get($checkoutData, 'addresses.shipping.line1', $defaultShipping?->line1)) }}" data-address-input="shipping">
                            <div class="address-suggestions" data-suggestions="shipping"></div>
                        </div>
                        <div class="field-row">
                            <input name="shipping[postal_code]" placeholder="Code postal" class="catalog-input" value="{{ old('shipping.postal_code', data_get($checkoutData, 'addresses.shipping.postal_code', $defaultShipping?->postal_code)) }}">
                            <input name="shipping[city]" placeholder="Ville" class="catalog-input" value="{{ old('shipping.city', data_get($checkoutData, 'addresses.shipping.city', $defaultShipping?->city)) }}">
                        </div>
                        <div class="field-row">
                            <select name="shipping[country_code]" class="catalog-input">
                                @foreach (['FR' => 'France', 'BE' => 'Belgique', 'LU' => 'Luxembourg', 'DE' => 'Allemagne', 'ES' => 'Espagne', 'IT' => 'Italie', 'PT' => 'Portugal'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('shipping.country_code', data_get($checkoutData, 'addresses.shipping.country_code', $defaultShipping?->country_code ?? 'FR')) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="shipping[phone]" placeholder="Téléphone (optionnel)" class="catalog-input" value="{{ old('shipping.phone', data_get($checkoutData, 'addresses.shipping.phone', $defaultShipping?->phone)) }}">
                        </div>
                    </div>

                    <label class="billing-copy">
                        <input type="checkbox" name="billing_same" value="1" @checked(old('billing_same', 1)) data-billing-toggle>
                        <span>Mes infos de facturation sont identiques à la livraison</span>
                    </label>

                    <div class="billing-fields @if(old('billing_same', 1)) is-hidden @endif" data-billing-fields>
                        <div class="field-grid two-cols">
                            <input name="billing[first_name]" placeholder="Prénom" class="catalog-input" value="{{ old('billing.first_name', data_get($checkoutData, 'addresses.billing.first_name', $defaultBilling?->first_name ?? auth()->user()?->first_name)) }}">
                            <input name="billing[last_name]" placeholder="Nom" class="catalog-input" value="{{ old('billing.last_name', data_get($checkoutData, 'addresses.billing.last_name', $defaultBilling?->last_name ?? auth()->user()?->last_name)) }}">
                            <div class="address-autocomplete">
                                <input name="billing[line1]" placeholder="Adresse" class="catalog-input" autocomplete="new-password" data-lpignore="true" data-form-type="other" autocapitalize="off" spellcheck="false" value="{{ old('billing.line1', data_get($checkoutData, 'addresses.billing.line1', $defaultBilling?->line1)) }}" data-address-input="billing">
                                <div class="address-suggestions" data-suggestions="billing"></div>
                            </div>
                            <div class="field-row">
                                <input name="billing[postal_code]" placeholder="Code postal" class="catalog-input" value="{{ old('billing.postal_code', data_get($checkoutData, 'addresses.billing.postal_code', $defaultBilling?->postal_code)) }}">
                                <input name="billing[city]" placeholder="Ville" class="catalog-input" value="{{ old('billing.city', data_get($checkoutData, 'addresses.billing.city', $defaultBilling?->city)) }}">
                            </div>
                            <div class="field-row">
                                <select name="billing[country_code]" class="catalog-input">
                                    @foreach (['FR' => 'France', 'BE' => 'Belgique', 'LU' => 'Luxembourg', 'DE' => 'Allemagne', 'ES' => 'Espagne', 'IT' => 'Italie', 'PT' => 'Portugal'] as $code => $label)
                                        <option value="{{ $code }}" @selected(old('billing.country_code', data_get($checkoutData, 'addresses.billing.country_code', $defaultBilling?->country_code ?? 'FR')) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input name="billing[phone]" placeholder="Téléphone (optionnel)" class="catalog-input" value="{{ old('billing.phone', data_get($checkoutData, 'addresses.billing.phone', $defaultBilling?->phone)) }}">
                            </div>
                        </div>
                    </div>
                    <button class="btn step-cta">Continuer</button>
                </form>
            </div>

            <div class="checkout-step" data-step-index="3">
                <div class="step-head">
                    <span class="step-index">03</span>
                    <div>
                        <h3>Livraison</h3>
                        <p>Choisissez le transporteur et ajoutez une note.</p>
                    </div>
                </div>
                <div class="step-alert step-alert--error is-hidden" data-step-errors="3"></div>
                @if($errors->has('shipping_method') || $errors->has('notes'))
                    <div class="step-alert step-alert--error">
                        <strong>Choisissez un transporteur :</strong>
                        <ul>
                            @php $msgs = array_merge($errors->get('shipping_method'), $errors->get('notes')); @endphp
                            @foreach($msgs as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('checkout.shipping') }}" class="step-body" data-step-next="4">
                    @csrf
                    <div id="shipping-options-container">
                        <div class="step-alert">Chargement des offres de livraison...</div>
                    </div>
                    <button type="submit" class="btn step-cta" disabled>Continuer</button>
                </form>
            </div>

            <div class="checkout-step" data-step-index="4">
                <div class="step-head">
                    <span class="step-index">04</span>
                    <div>
                        <h3>Paiement</h3>
                        <p>Sélectionnez votre mode de paiement.</p>
                    </div>
                </div>
                @if($errors->has('payment'))
                    <div class="step-alert step-alert--error">
                        <strong>Paiement :</strong>
                        <ul>
                            @foreach($errors->get('payment') as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($errors->has('payment.provider') || $errors->has('payment.method') || $errors->has('notes'))
                    <div class="step-alert step-alert--error">
                        <strong>Complétez le paiement :</strong>
                        <ul>
                            @php $msgs = array_merge($errors->get('payment.provider'), $errors->get('payment.method'), $errors->get('notes')); @endphp
                            @foreach($msgs as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @php
                    $allowDevMode = (bool) config('checkout.allow_dev_mode', config('app.debug'));
                @endphp
                <form method="POST" action="{{ route('checkout.payment') }}" class="step-body" id="paymentForm">
                    @csrf
                    <input type="hidden" name="payment[provider]" value="{{ old('payment.provider', 'stripe') }}" data-provider-hidden>
                    <div class="pay-grid" data-payment-options>
                        <div class="pay-card-wrap">
                            <input class="pay-radio" type="radio" name="payment[method]" id="pay-card" value="card" @checked(old('payment.method', 'card') === 'card')>
                            <label class="payment-card-modern payment-option" data-method="card" for="pay-card">
                                <div class="left">
                                    <img class="pay-logo" src="https://assets.streamlinehq.com/image/private/w_50,h_50,ar_1/f_auto/v1/icons/4/stripe-i7qiowltd98dwi26h7gqgs.png/stripe-fa8s7wqr3uutk29ccbbhyh.png?_a=DATAg1AAZAA0" alt="Stripe">
                                    <div>
                                        <div class="ship-name">Stripe - Paiement par carte</div>
                                        <div class="card-pill-row">
                                            <span class="card-pill"><img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" alt="Mastercard"></span>
                                            <span class="card-pill"><img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" alt="Apple Pay"></span>
                                            <span class="card-pill"><img class="gpay-zoom" src="https://e7.pngegg.com/pngimages/849/112/png-clipart-google-pay-send-android-computer-icons-android-text-trademark.png" alt="Google Pay"></span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="pay-card-wrap">
                            <input class="pay-radio" type="radio" name="payment[method]" id="pay-paypal" value="paypal" @checked(old('payment.method') === 'paypal')>
                            <label class="payment-card-modern payment-option" data-method="paypal" for="pay-paypal">
                                <div class="left">
                                    <img class="pay-logo" src="https://i0.wp.com/www.frenchweb.fr/wp-content/uploads/2023/02/LOGO-850-paypal.png?resize=850%2C478&ssl=1" alt="PayPal">
                                    <div>
                                        <div class="ship-name">PayPal - Paiement sécurisé</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="paypal-inline" data-paypal-button style="display:none;">
                            <div id="paypal-button-container"></div>
                        </div>
                        @if($allowDevMode)
                            <div class="pay-card-wrap">
                                <input class="pay-radio" type="radio" name="payment[method]" id="pay-dev" value="dev" @checked(old('payment.method') === 'dev')>
                                <label class="payment-card-modern payment-option" data-method="dev" for="pay-dev">
                                    <div class="left">
                                        <img class="pay-logo" src="https://cdn-icons-png.flaticon.com/512/565/565296.png" alt="Dev" style="width:36px;height:36px;">
                                        <div>
                                            <div class="ship-name">Mode dev</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endif
                    </div>
                    <button class="btn step-cta">Continuer</button>
                </form>
            </div>
        </div>
        </div>

        <aside class="checkout-summary">
            <div class="summary-card">
                <div class="summary-head">
                    <h2>Votre commande</h2>
                    @if($allowFreeShipping && ! $forcedShipping && $remainingForFree > 0 && $freeShippingMin)
                        <p class="summary-hint summary-hint--left">Encore {{ number_format($remainingForFree, 2, ',', ' ') }} € pour la livraison offerte</p>
                    @elseif($allowFreeShipping && ! $forcedShipping && $freeShippingMin)
                        <p class="summary-hint summary-hint--left">Livraison offerte sur votre panier 🎉</p>
                    @endif
                </div>
                <ul class="summary-items">
                    @foreach ($cart->items as $item)
                        @php
                            $brand = $item->product->brand?->name;
                            $legacyModel = data_get($item->variant?->attributes, 'legacy_model') ?? data_get($item->product?->attributes, 'legacy_model');
                            $legacyRepairId = data_get($item->variant?->attributes, 'legacy_repair_id');
                            $legacyRepairModel = null;
                            if ($legacyRepairId) {
                                $legacy = \App\Models\Legacy\Repair::find($legacyRepairId);
                                $legacyRepairModel = $legacy?->model;
                            }
                            $model = $legacyModel ?? $legacyRepairModel;
                            $optionTags = [];
                            $optionList = data_get($item->variant_snapshot, 'options', []);
                            if (is_array($optionList)) {
                                foreach ($optionList as $opt) {
                                    $label = is_scalar(data_get($opt, 'label')) ? trim((string) data_get($opt, 'label')) : '';
                                    if ($label === '' || strcasecmp($label, 'Livraison') === 0) {
                                        continue;
                                    }
                                    $value = is_scalar(data_get($opt, 'value')) ? trim((string) data_get($opt, 'value')) : '';
                                    $price = is_numeric(data_get($opt, 'price')) ? (float) data_get($opt, 'price') : null;
                                    $textParts = [];
                                    if ($value !== '') {
                                        $textParts[] = $label.': '.$value;
                                    } else {
                                        $textParts[] = $label;
                                    }
                                    if ($price !== null && $price > 0) {
                                        $textParts[] = '+'.number_format($price, 2, ',', ' ').' €';
                                    }
                                    if ($textParts) {
                                        $optionTags[] = implode(' ', $textParts);
                                    }
                                }
                            }
                        @endphp
                        <li class="summary-item">
                            <div>
                                @if($brand || $model)
                                    <span class="muted">{{ trim($brand.' - '.$model, ' -') }}</span>
                                @endif
                                <strong>{{ $item->name }}</strong>
                                @if($optionTags)
                                    <span class="muted">{{ implode(' - ', $optionTags) }}</span>
                                @endif
                                <span class="muted">x{{ $item->quantity }}</span>
                            </div>
                            <span>{{ number_format($item->unit_price_ttc * $item->quantity, 2, ',', ' ') }} €</span>
                        </li>
                    @endforeach
                </ul>
                <div class="summary-rows" data-summary
                     data-subtotal="{{ $cart->subtotal_ttc }}"
                     data-discount="{{ $cart->discount_total }}"
                     data-shipping="{{ $shippingSelected ? $shippingDisplay : 0 }}">
                    <div class="summary-row">
                        <span>Sous-total</span>
                        <span>{{ number_format($cart->subtotal_ttc, 2, ',', ' ') }} €</span>
                    </div>
                    @if($cart->discount_total > 0)
                        <div class="summary-row summary-row--discount">
                            <span>Remises</span>
                            <span>-{{ number_format($cart->discount_total, 2, ',', ' ') }} €</span>
                        </div>
                    @endif
                    <div class="summary-row" id="summary-shipping-row" @if(!$shippingSelected) style="display:none;" @endif>
                        <span>Livraison</span>
                        <span id="summary-shipping-amount">
                            @if($shippingSelected)
                                {{ number_format($shippingDisplay, 2, ',', ' ') }} €
                            @endif
                        </span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row summary-total">
                        <span>Total TTC</span>
                        <span id="summary-total-amount">{{ number_format($summaryTotal, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stepper = document.querySelector('[data-stepper]');
            if (stepper) {
                const steps = Array.from(stepper.querySelectorAll('[data-step-index]'));
                const dots = Array.from(stepper.querySelectorAll('[data-step-target]'));
                const progressEl = stepper.querySelector('.checkout-progress');
                let maxStep = Number(progressEl?.dataset.maxStep || '1');

                const setActive = (target) => {
                    steps.forEach((step) => {
                        const idx = Number(step.dataset.stepIndex);
                        step.classList.toggle('is-active', idx === target);
                        step.classList.toggle('is-before', idx < target);
                    });
                    dots.forEach((dot) => {
                        const idx = Number(dot.dataset.stepTarget);
                        dot.classList.toggle('is-active', idx === target);
                        dot.classList.toggle('is-complete', idx < target);
                    });
                };

                dots.forEach((dot) => {
                    dot.addEventListener('click', () => {
                        const target = Number(dot.dataset.stepTarget);
                        if (target > maxStep + 1) return;
                        setActive(target);
                    });
                });

                const initial = Number(stepper.dataset.activeStep || '1');
                setActive(initial);

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const showErrors = (stepIndex, messages) => {
                    const box = stepper.querySelector(`[data-step-errors="${stepIndex}"]`);
                    if (!box) return;
                    if (!messages || messages.length === 0) {
                        box.classList.add('is-hidden');
                        box.innerHTML = '';
                        return;
                    }
                    box.innerHTML = `<strong>Corriger ces éléments :</strong><ul>${messages.map((m) => `<li>${m}</li>`).join('')}</ul>`;
                    box.classList.remove('is-hidden');
                };

                const submitStep = async (form, nextStep) => {
                    const stepIndex = Number(form.closest('[data-step-index]')?.dataset.stepIndex || nextStep - 1);
                    showErrors(stepIndex, []);
                    const formData = new FormData(form);
                    try {
                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: formData,
                        });

                        if (response.status === 422) {
                            const payload = await response.json();
                            const messages = payload.errors ? Object.values(payload.errors).flat() : ['Données invalides.'];
                            showErrors(stepIndex, messages);
                            return;
                        }

                        if (!response.ok) {
                            showErrors(stepIndex, ['Une erreur est survenue.']);
                            return;
                        }

                        maxStep = Math.max(maxStep, nextStep);
                        if (progressEl) progressEl.dataset.maxStep = String(maxStep);
                        setActive(nextStep);
                        showErrors(stepIndex, []);
                    } catch (e) {
                        showErrors(stepIndex, ['Connexion interrompue, réessayez.']);
                    }
                };

                stepper.querySelectorAll('form[data-step-next]').forEach((form) => {
                    const nextStep = Number(form.dataset.stepNext);
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();
                        void submitStep(form, nextStep);
                    });
                });

                const billingToggle = stepper.querySelector('[data-billing-toggle]');
                const billingFields = stepper.querySelector('[data-billing-fields]');
                billingToggle?.addEventListener('change', () => {
                    if (!billingFields) return;
                    billingFields.classList.toggle('is-hidden', billingToggle.checked);
                });
            }

            const form = document.getElementById('paymentForm');
            if (!form) return;

            // Ouvrir Stripe dans une popup (ou nouvel onglet en fallback)
            form.addEventListener('submit', (event) => {
                const method = form.querySelector('input[name="payment[method]"]:checked')?.value;
                if (method === 'card') {
                    const popup = window.open('', 'stripePopup', 'width=620,height=780,left=120,top=60');
                    if (popup) {
                        form.target = 'stripePopup';
                        popup.focus();
                    } else {
                        form.target = '_blank';
                    }
                } else if (method !== 'paypal') {
                    form.removeAttribute('target');
                }
            });

            // Bouton PayPal inline (hosted button officiel)
            const paypalButton = document.querySelector('[data-paypal-button]');
            const paypalSuccessUrl = @json(route('checkout.paypal.success'));
            let paypalOrderNumber = null;
            let paypalConfirmationUrl = null;
            let paypalRendered = false;
            const methodRadios = Array.from(form.querySelectorAll('input[name="payment[method]"]'));
            const togglePaypalButton = () => {
                const method = form.querySelector('input[name="payment[method]"]:checked')?.value;
                if (paypalButton) {
                    paypalButton.style.display = method === 'paypal' ? 'block' : 'none';
                    if (method === 'paypal') {
                        renderHosted();
                    }
                }
            };
            togglePaypalButton();
            methodRadios.forEach((radio) => radio.addEventListener('change', togglePaypalButton));
            // Rendre toute la carte cliquable pour selectionner la methode
            document.querySelectorAll('.payment-option').forEach((option) => {
                option.addEventListener('click', (e) => {
                    const wrap = option.closest('.pay-card-wrap');
                    const input = wrap ? wrap.querySelector('input[type="radio"]') : option.querySelector('input[type="radio"]');
                    if (input) {
                        input.checked = true;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
            const renderHosted = () => {
                if (paypalRendered) return;
                if (window.paypal && window.paypal.Buttons) {
                    window.paypal.Buttons({
                        style: {
                            layout: 'horizontal',
                            color: 'gold',
                            shape: 'rect',
                            label: 'paypal',
                            height: 38,
                        },
                        createOrder: async (data, actions) => {
                            const formData = new FormData(form);
                            const rejectOrder = (message) => {
                                if (message) {
                                    alert(message);
                                }
                                return actions?.reject ? actions.reject() : Promise.reject(new Error(message || 'Erreur PayPal'));
                            };
                            try {
                                const response = await fetch(form.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                    },
                                    body: formData,
                                });
                                const payload = await response.json().catch(() => ({}));
                                if (!response.ok) {
                                    return rejectOrder(payload?.error || 'Erreur PayPal');
                                }
                                if (payload?.order_number) {
                                    paypalOrderNumber = payload.order_number;
                                    paypalConfirmationUrl = payload.confirmation_url || ("/checkout/confirmation/" + payload.order_number);
                                }
                                if (payload?.paypal_order_id) {
                                    return payload.paypal_order_id;
                                }
                                return rejectOrder(payload?.error || 'Erreur PayPal');
                            } catch (err) {
                                return rejectOrder('Erreur PayPal');
                            }
                        },
                        onApprove: (data) => {
                            const orderId = data?.orderID;
                            if (!orderId) {
                                window.location.reload();
                                return;
                            }
                            // On laisse le backend capturer et finaliser, puis on redirige vers la confirmation
                            let finalizeUrl = paypalSuccessUrl + '?token=' + encodeURIComponent(orderId);
                            if (paypalOrderNumber) {
                                finalizeUrl += '&order_number=' + encodeURIComponent(paypalOrderNumber);
                            }
                            window.location.href = finalizeUrl;
                        },
                    }).render('#paypal-button-container');
                    paypalRendered = true;
                }
            };
const providerHidden = form.querySelector('[data-provider-hidden]');
            const methodInputs = Array.from(form.querySelectorAll('input[name="payment[method]"]'));
            const syncProvider = () => {
                const current = form.querySelector('input[name="payment[method]"]:checked')?.value;
                let provider = 'stripe';
                if (current === 'paypal') provider = 'paypal';
                else if (current === 'bank_transfer') provider = 'manual';
                else if (current === 'dev') provider = 'dev';
                providerHidden.value = provider;
                if (current !== 'paypal' && paypalRendered) {
                    const container = document.querySelector('.paypal-inline');
                    if (container) container.style.display = 'none';
                }
                if (current === 'paypal') renderHosted();
            };

            methodInputs.forEach((input) => {
                input.addEventListener('change', syncProvider);
            });

            // init
            syncProvider();
        });
    </script>

    <style>
        .address-autocomplete { position: relative; }
        .address-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #dfe5ef;
            border-top: none;
            z-index: 20;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 8px 20px rgba(17, 17, 17, 0.08);
            display: none;
        }
        .address-suggestions button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border: none;
            background: #fff;
            font-size: 14px;
            cursor: pointer;
        }
        .address-suggestions button:hover {
            background: #f2f7ff;
        }
        @media (max-width: 900px) {
            .checkout-layout {
                display: grid;
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .checkout-summary {
                order: -1;
                position: static;
                width: 100%;
            }
            .checkout-step {
                padding: 16px;
            }
            .checkout-steps {
                gap: 16px;
            }
            .checkout-head {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const setupAutocomplete = (inputName, postalName, cityName, suggestionsAttr) => {
                const input = document.querySelector(`input[name="${inputName}"]`);
                const postal = document.querySelector(`input[name="${postalName}"]`);
                const city = document.querySelector(`input[name="${cityName}"]`);
                const box = document.querySelector(`[data-suggestions="${suggestionsAttr}"]`);
                if (!input || !postal || !city || !box) return;

                let timer;
                const render = (items) => {
                    box.innerHTML = '';
                    if (!items.length) { box.style.display = 'none'; return; }
                    items.forEach((it) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.innerHTML = `<strong>${it.label}</strong><br><span style="color:#6b7280;font-size:12px;">${it.city} (${it.postcode})</span>`;
                        btn.addEventListener('click', () => {
                            input.value = it.line1;
                            postal.value = it.postcode;
                            city.value = it.city;
                            box.style.display = 'none';
                        });
                        box.appendChild(btn);
                    });
                    box.style.display = 'block';
                };

                input.addEventListener('input', () => {
                    const q = input.value.trim();
                    if (timer) clearTimeout(timer);
                    if (q.length < 4) { box.style.display = 'none'; return; }
                    timer = setTimeout(async () => {
                        try {
                            const resp = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(q)}&limit=5`);
                            const data = await resp.json();
                            const items = (data.features || []).map((f) => {
                                const props = f.properties || {};
                                const streetLine = props.name || props.street || props.label || '';
                                return {
                                    label: props.label || '',
                                    line1: streetLine,
                                    city: props.city || props.name || '',
                                    postcode: props.postcode || '',
                                };
                            }).filter(it => it.label && it.postcode && it.line1);
                            render(items);
                        } catch (e) {
                            box.style.display = 'none';
                        }
                    }, 200);
                });

                document.addEventListener('click', (e) => {
                    if (!box.contains(e.target) && e.target !== input) {
                        box.style.display = 'none';
                    }
                });
            };

            setupAutocomplete('shipping[line1]', 'shipping[postal_code]', 'shipping[city]', 'shipping');
            setupAutocomplete('billing[line1]', 'billing[postal_code]', 'billing[city]', 'billing');
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const summaryEl = document.querySelector('[data-summary]');
            const shippingRow = document.getElementById('summary-shipping-row');
            const shippingAmountEl = document.getElementById('summary-shipping-amount');
            const totalEl = document.getElementById('summary-total-amount');
            const baseSubtotal = parseFloat(summaryEl?.dataset.subtotal || '0');
            const baseDiscount = parseFloat(summaryEl?.dataset.discount || '0');
            let shippingAddress = @json($shippingAddress);
            const shippingForm = document.querySelector('[data-step-index="3"] form');
            const shippingButton = shippingForm?.querySelector('button[type="submit"]');

            const formatPrice = (val) => {
                return val.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            };

            const toggleShippingButton = (enabled) => {
                if (!shippingButton) return;
                shippingButton.disabled = !enabled;
            };

            const refreshSummary = () => {
                const checked = document.querySelector('.ship-radio:checked');
                const price = checked ? parseFloat(checked.dataset.price || '0') : 0;
                if (checked && shippingRow && shippingAmountEl) {
                    shippingRow.style.display = '';
                    shippingAmountEl.textContent = formatPrice(price);
                } else if (shippingRow) {
                    shippingRow.style.display = 'none';
                    if (shippingAmountEl) shippingAmountEl.textContent = '';
                }
                if (totalEl) {
                    const total = baseSubtotal - baseDiscount + (checked ? price : 0);
                    totalEl.textContent = formatPrice(total);
                }
            };

            const bindShippingRadios = () => {
                document.querySelectorAll('.ship-radio').forEach((r) => {
                    r.removeEventListener('change', refreshSummary);
                    r.addEventListener('change', refreshSummary);
                });
                refreshSummary();
                toggleShippingButton(document.querySelectorAll('.ship-radio').length > 0);
            };

            const loadShippingOptions = async () => {
                const container = document.getElementById('shipping-options-container');
                // Si pas d'adresse en session, tente de récupérer les champs du formulaire adresses (étape 2)
                if (!shippingAddress || !shippingAddress.postal_code || !shippingAddress.country_code) {
                    const form = document.querySelector('form[action*="checkout/adresses"]') || document.querySelector('[data-step-index="2"] form');
                    if (form) {
                        const getVal = (name) => form.querySelector(`[name="${name}"]`)?.value?.trim() || '';
                        const candidate = {
                            line1: getVal('shipping[line1]'),
                            postal_code: getVal('shipping[postal_code]'),
                            city: getVal('shipping[city]'),
                            country_code: getVal('shipping[country_code]') || 'FR',
                            type: getVal('shipping[type]') || 'particulier',
                        };
                        if (candidate.postal_code && candidate.country_code) {
                            shippingAddress = candidate;
                        }
                    }
                }
                if (!shippingAddress || !shippingAddress.postal_code || !shippingAddress.country_code) {
                    // Options fixes : on peut afficher les transporteurs même sans adresse.
                    shippingAddress = shippingAddress || {};
                }
                if (container) {
                    container.innerHTML = '<div class="step-alert">Chargement des offres de livraison...</div>';
                }
                let timedOut = false;
                const to = setTimeout(() => {
                    timedOut = true;
                    if (container) container.innerHTML = '<div class="step-alert step-alert--error">Boxtal ne répond pas (timeout).</div>';
                }, 6000);
                try {
                    const params = new URLSearchParams();
                    Object.entries(shippingAddress || {}).forEach(([k, v]) => {
                        if (v !== undefined && v !== null) {
                            params.append(`shipping[${k}]`, v);
                        }
                    });
                    const qs = params.toString();
                    const url = '{{ route('checkout.shipping.options') }}' + (qs ? ('?' + qs) : '');
                    const resp = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                    });
                    clearTimeout(to);
                    if (timedOut) return;
                    const text = await resp.text();
                    if (!resp.ok) {
                        if (container) container.innerHTML = '<div class="step-alert step-alert--error">Boxtal indisponible (HTTP ' + resp.status + ')</div><pre style="white-space:pre-wrap;font-size:11px;">'+text+'</pre>';
                        toggleShippingButton(false);
                        return;
                    }
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        if (container) container.innerHTML = '<div class="step-alert step-alert--error">Réponse Boxtal invalide.</div><pre style="white-space:pre-wrap;font-size:11px;">'+text+'</pre>';
                        toggleShippingButton(false);
                        return;
                    }
                    if (data?.html && container) {
                        container.innerHTML = data.html;
                        bindShippingRadios();
                        toggleShippingButton(container.querySelectorAll('.ship-radio').length > 0);
                    } else if (container) {
                        container.innerHTML = '<div class="step-alert step-alert--error">Aucune option Boxtal disponible.</div>';
                        toggleShippingButton(false);
                    }
                } catch (e) {
                    clearTimeout(to);
                    if (container) container.innerHTML = '<div class="step-alert step-alert--error">Erreur Boxtal : ' + (e?.message || 'requête échouée') + '</div>';
                    toggleShippingButton(false);
                }
            };

            bindShippingRadios();
            toggleShippingButton(document.querySelectorAll('.ship-radio').length > 0);

            // Charge Boxtal uniquement si on a déjà une adresse
            loadShippingOptions();

            // Soumission AJAX du formulaire adresses (étape 2)
            const addressForm = document.querySelector('form[action*="checkout/adresses"]');
            if (addressForm) {
                addressForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(addressForm);
                    try {
                        const resp = await fetch(addressForm.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        });
                        if (!resp.ok) {
                            alert('Erreur enregistrement adresses ('+resp.status+')');
                            return;
                        }
                        const data = await resp.json();
                        if (data?.addresses?.shipping) {
                            shippingAddress = data.addresses.shipping;
                        }
                        loadShippingOptions();
                        // Active l'étape 3 visuellement
                        const stepper = document.querySelector('[data-stepper]');
                        if (stepper) stepper.dataset.activeStep = '3';
                        const dots = document.querySelectorAll('[data-step-target]');
                        dots.forEach((dot) => {
                            const idx = Number(dot.dataset.stepTarget);
                            dot.classList.toggle('is-active', idx === 3);
                            dot.classList.toggle('is-complete', idx < 3);
                        });
                    } catch (err) {
                        alert('Erreur adresses');
                    }
                });
            }
        });
    </script>
@endsection

