@extends('layouts.app', ['title' => 'Panier'])

@section('content')
    <style>
        .cart-free-sticky {
            display: none;
        }

        .promo-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            padding: 12px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ecfdf3, #d1fae5);
            border: 1px solid #a7f3d0;
            box-shadow: 0 6px 14px rgba(16, 185, 129, 0.12);
            justify-content: flex-start;
        }
        .promo-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #10b981;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }
        .promo-banner-text {
            line-height: 1.35;
            color: #065f46;
            flex: 1;
        }
        .promo-banner-title {
            font-weight: 800;
            font-size: 14px;
        }
        .promo-banner-meta {
            font-size: 13px;
            font-weight: 600;
            color: #047857;
        }
        .summary-checkout.is-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            border: 1px solid #cbd5e1;
        }
        .promo-remove-form {
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        .promo-remove {
            margin-left: 18px;
        }
        .promo-remove-form {
            margin-left: auto;
        }
        @media (max-width: 1024px) {
            body {
                background: linear-gradient(180deg, #f5f7fb 0%, #eef2ff 40%, #ffffff 100%);
                overflow-x: hidden;
            }
            main.container {
                padding: 18px 0 130px !important;
                width: 100%;
                max-width: 100%;
                margin: 0;
                box-sizing: border-box;
            }
            .cart-layout {
                grid-template-columns: 1fr;
                gap: 14px;
                padding: 0 12px;
                width: 100%;
                margin: 0 auto;
                box-sizing: border-box;
            }
            .support-card {
                padding: 1.15rem;
                border-radius: 20px;
            }
            .cart-summary {
                order: -1;
                position: static;
                width: 100%;
            }
            .cart-item-list {
                gap: 12px;
            }
            .cart-card {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 1rem;
                border-radius: 18px;
            }
            .cart-card__media {
                width: 100%;
                border-radius: 16px;
                background: #f5f7fb;
                padding: 10px;
                box-shadow: inset 0 0 0 1px rgba(17, 17, 17, 0.03);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .cart-card__media img {
                width: auto;
                height: auto;
                max-width: 70%;
                max-height: 170px;
                object-fit: contain;
                border-radius: 12px;
                background: transparent;
                box-shadow: none;
            }
            .cart-card__top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .cart-card__price {
                min-width: 0;
                width: 100%;
                padding: 0.85rem 1rem;
                border-radius: 14px;
                text-align: left;
            }
            .cart-card__price .price-line {
                flex-direction: column;
                align-items: flex-start;
                justify-content: flex-start;
                gap: 2px;
            }
            .cart-card__bottom {
                display: grid;
                grid-template-columns: 1fr auto;
                align-items: center;
                gap: 10px;
            }
            .cart-card__qty {
                width: 100%;
                justify-content: space-between;
            }
            .quantity-control {
                box-shadow: 0 14px 32px rgba(3, 118, 184, 0.14);
            }
            .quantity-control button {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            .quantity-control input {
                width: 52px;
                font-size: 0.95rem;
            }
            .cart-card__actions {
                margin-left: 0;
                justify-content: flex-end;
            }
            .cart-card__remove .icon-btn {
                padding: 10px 12px !important;
                border-radius: 12px !important;
            }
            .cart-summary .summary-extra {
                display: none;
            }
            .cart-free-sticky {
                display: block;
                position: fixed;
                left: max(12px, env(safe-area-inset-left, 12px));
                right: max(12px, env(safe-area-inset-right, 12px));
                bottom: max(12px, env(safe-area-inset-bottom, 12px));
                z-index: 25000;
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(15, 23, 42, 0.08);
                box-shadow: 0 22px 50px rgba(15, 23, 42, 0.16);
                border-radius: 18px;
                padding: 12px 14px;
                font-weight: 800;
                color: #0b1f4f;
            }
        }

        @media (max-width: 540px) {
            .promo-input-group {
                grid-template-columns: 1fr;
            }
            .promo-input-group .btn-secondary {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
    @php
        $isCartEmpty = $cart->items->isEmpty();
    @endphp
    <section class="catalog-layout cart-layout">
        <div class="support-card">
            <h2 class="section-title" style="text-align:left;margin-top:0;">Mon panier</h2>
            <div class="cart-item-list">
                @forelse ($cart->items as $item)
                    @php
                        $thumb = data_get($item->variant_snapshot, 'image_url');
                        if (! is_string($thumb)) {
                            $thumb = null;
                        }
                        $brand = $item->product?->brand?->name;
                        $brand = is_scalar($brand) ? (string) $brand : null;
                        $quality = data_get($item->variant_snapshot, 'quality') ?? $item->variant?->quality;
                        $quality = is_scalar($quality) ? trim((string) $quality) : null;
                        $revision = data_get($item->variant_snapshot, 'revision') ?? $item->variant?->revision;
                        $revision = is_scalar($revision) ? trim((string) $revision) : null;
                        $color = data_get($item->variant_snapshot, 'color') ?? $item->variant?->color;
                        $color = is_scalar($color) ? trim((string) $color) : null;
                        $colorLabel = $color;
                        $optionTags = [];
                        $optionList = data_get($item->variant_snapshot, 'options', []);
                        if (is_array($optionList)) {
                            foreach ($optionList as $opt) {
                                $label = is_scalar(data_get($opt, 'label')) ? trim((string) data_get($opt, 'label')) : '';
                                $value = is_scalar(data_get($opt, 'value')) ? trim((string) data_get($opt, 'value')) : '';
                                $price = is_numeric(data_get($opt, 'price')) ? (float) data_get($opt, 'price') : null;
                                $textParts = [];
                                if ($label !== '' && $value !== '') {
                                    $textParts[] = $label.': '.$value;
                                } elseif ($label !== '') {
                                    $textParts[] = $label;
                                } elseif ($value !== '') {
                                    $textParts[] = $value;
                                }
                                if ($price !== null && $price > 0) {
                                    $textParts[] = '+'.number_format($price, 2, ',', ' ').' €';
                                }
                                if ($textParts) {
                                    $optionTags[] = implode(' ', $textParts);
                                }
                            }
                        }
                        if ($color) {
                            $colorMap = [
                                'purple' => 'Violet',
                                'pink' => 'Rose',
                                'black' => 'Noir',
                                'white' => 'Blanc',
                                'red' => 'Rouge',
                                'blue' => 'Bleu',
                                'green' => 'Vert',
                                'yellow' => 'Jaune',
                                'orange' => 'Orange',
                                'grey' => 'Gris',
                                'gray' => 'Gris',
                                'teal' => 'Turquoise',
                                'gold' => 'Or',
                            ];
                            $key = strtolower(trim($color));
                            $colorLabel = $colorMap[$key] ?? $color;
                        }
                        if (! $thumb) {
                            $thumb = $item->product?->image_url ?? $item->variant?->product?->image_url;
                        }
                        if (! is_string($thumb)) {
                            $thumb = null;
                        } elseif ($thumb !== '' && ! str_starts_with($thumb, 'http://') && ! str_starts_with($thumb, 'https://') && ! str_starts_with($thumb, '//')) {
                            $thumb = asset($thumb);
                        }
                        $thumb ??= asset('assets/img/service1.webp');
                    @endphp
                    <article class="cart-card">
                        <div class="cart-card__media">
                            <img src="{{ $thumb }}" alt="{{ $item->name }}">
                        </div>
                        <div class="cart-card__body">
                            <div class="cart-card__top">
                                <div class="cart-card__title">
                                    @php
                                        $qualitySuffix = '';
                                        if ($quality) {
                                            $qualityText = function_exists('mb_strtolower') ? mb_strtolower($quality) : strtolower($quality);
                                            $itemNameLower = function_exists('mb_strtolower') ? mb_strtolower((string) ($item->name ?? '')) : strtolower((string) ($item->name ?? ''));
                                            $qualityLower = function_exists('mb_strtolower') ? mb_strtolower((string) $quality) : strtolower((string) $quality);
                                            if ($qualityLower !== '' && ! str_contains($itemNameLower, $qualityLower)) {
                                                $qualitySuffix = ' ' . $qualityText;
                                            }
                                        }

                                        $titleMeta = collect([
                                            $brand,
                                            $color ? ('Couleur ' . $colorLabel) : null,
                                        ])->filter()->implode(' - ');
                                    @endphp
                                    <h3>@if($brand){{ $brand }} - @endif{{ $item->name }}{{ $qualitySuffix }}@if($color) - Couleur {{ $colorLabel }}@endif</h3>
                                    @if ($revision)
                                        <div class="cart-card__meta">
                                            <span class="cart-tag cart-tag--muted">Révision {{ strtoupper($revision) }}</span>
                                        </div>
                                    @endif
                                    @if ($optionTags)
                                        <div class="cart-card__meta">
                                            @foreach ($optionTags as $tag)
                                                <span class="cart-tag cart-tag--muted">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="cart-card__price">
                                    <div class="price-line">
                                        <span>Prix unitaire</span>
                                        <strong>{{ number_format($item->unit_price_ttc, 2, ',', ' ') }} €</strong>
                                    </div>
                                    <div class="price-line price-line--total">
                                        <span>Total</span>
                                        <strong>{{ number_format($item->unit_price_ttc * $item->quantity, 2, ',', ' ') }} €</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="cart-card__bottom">
                                <form method="POST" action="{{ route('cart.update', $item) }}" class="cart-card__qty">
                                    @csrf
                                    @method('PATCH')
                                    <span class="cart-card__control-label">Quantité</span>
                                    <div class="quantity-control">
                                        <button type="button" class="cart-qty-decrease" data-delete-target="delete-item-{{ $item->id }}">−</button>
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" inputmode="numeric" data-cart-qty>
                                        <button type="button" class="cart-qty-increase">+</button>
                                    </div>
                                </form>
                                <div class="cart-card__actions">
                                    <form method="POST" action="{{ route('cart.destroy', $item) }}" id="delete-item-{{ $item->id }}" class="cart-card__remove">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn" aria-label="Supprimer l'article" style="padding:12px 14px;background:linear-gradient(135deg,#f87171,#dc2626);color:#fff;border:none;border-radius:14px;box-shadow:0 10px 24px rgba(220,38,38,0.18);display:inline-flex;align-items:center;justify-content:center;">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <p>Votre panier est vide.</p>
                @endforelse
            </div>
        </div>

        <aside class="support-card cart-summary">
            <h2 class="section-title" style="text-align:left;margin-top:0;">Récapitulatif</h2>
            @php
                $freeShippingMin = config('pricing.free_shipping_min_total');
                $smallOrderFee = config('pricing.small_order_shipping_fee');
                $shippingSurcharge = config('pricing.shipping_surcharge', 0);
                $forcedShipping = $cart->forcedShippingFee();
                // Eligibilité calculée sur le panier avant remise pour ne pas annuler la livraison offerte
                $eligibleBase = max(0, $cart->subtotal_ttc);
                $allowFreeShipping = $shippingSurcharge <= 0;
                $remainingForFree = ($allowFreeShipping && ! $forcedShipping)
                    ? ($freeShippingMin ? max(0, $freeShippingMin - $eligibleBase) : 0)
                    : 0;
            @endphp
            <div class="summary-rows">
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
                <!-- Ligne livraison masquée : calculée au checkout -->
            <div class="summary-divider"></div>
                <div class="summary-row summary-total">
                    <span>Total TTC</span>
                    <span>{{ number_format($cart->subtotal_ttc - $cart->discount_total, 2, ',', ' ') }} €</span>
                </div>
            </div>

            <div class="summary-extra">
                @if($allowFreeShipping && ! $forcedShipping && $freeShippingMin)
                    <p class="summary-badge">
                        @if($remainingForFree > 0)
                            Encore {{ number_format($remainingForFree, 2, ',', ' ') }} € pour la livraison offerte
                        @else
                            Livraison offerte sur votre panier 🎉
                        @endif
                    </p>
                @endif
            </div>

            <form method="POST" action="{{ route('cart.promo') }}" class="promo-form">
                @csrf
                <label for="code" class="promo-label">Code promo</label>
                <div class="promo-input-group @error('code') is-error @enderror">
                    <input type="text" id="code" name="code" class="catalog-input" placeholder="Votre code" value="{{ old('code', $cart->promoCode?->code ?? '') }}">
                    <button class="btn btn-secondary" type="submit">Appliquer</button>
                </div>
                @error('code')
                    <p class="promo-error">{{ $message }}</p>
              @enderror
                @if($cart->promoCode)
                    <div class="promo-banner">
                        <div class="promo-badge">✓</div>
                        <div class="promo-banner-text">
                            <div class="promo-banner-title">Code {{ strtoupper($cart->promoCode->code) }} appliqué</div>
                            @if($cart->discount_total > 0)
                                <div class="promo-banner-meta">Économies : -{{ number_format($cart->discount_total, 2, ',', ' ') }} €</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('cart.promo.remove') }}" class="promo-remove-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="promo-remove icon-btn" aria-label="Retirer le code promo" style="padding:8px 10px;background:linear-gradient(135deg,#f87171,#dc2626);color:#fff;border:none;border-radius:10px;box-shadow:0 8px 18px rgba(220,38,38,0.18);display:inline-flex;align-items:center;justify-content:center;">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endif
            </form>

            <a href="{{ $isCartEmpty ? '#' : route('checkout.index') }}"
               class="btn summary-checkout @if($isCartEmpty) is-disabled @endif"
               @if($isCartEmpty) aria-disabled="true" onclick="event.preventDefault();" @endif>
                Payer
            </a>
        </aside>

        @if($allowFreeShipping && ! $forcedShipping && ($freeShippingMin ?? null) && ! $isCartEmpty)
            <div class="cart-free-sticky">
                @if($remainingForFree > 0)
                    Encore {{ number_format($remainingForFree, 2, ',', ' ') }} € pour la livraison offerte
                @else
                    Livraison offerte sur votre panier
                @endif
            </div>
        @endif
    </section>

    <script>
        (() => {
            const replaceFromHTML = (html) => {
                if (!html) return;
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nextList = doc.querySelector('.cart-item-list');
                const nextSummary = doc.querySelector('.cart-summary');
                const nextSticky = doc.querySelector('.cart-free-sticky');
                const currentList = document.querySelector('.cart-item-list');
                const currentSummary = document.querySelector('.cart-summary');
                const currentSticky = document.querySelector('.cart-free-sticky');

                if (nextList && currentList?.parentElement) {
                    currentList.parentElement.replaceChild(nextList, currentList);
                }
                if (nextSummary && currentSummary?.parentElement) {
                    currentSummary.parentElement.replaceChild(nextSummary, currentSummary);
                }
                if (nextSticky) {
                    if (currentSticky?.parentElement) {
                        currentSticky.parentElement.replaceChild(nextSticky, currentSticky);
                    } else {
                        document.body.appendChild(nextSticky);
                    }
                } else if (currentSticky) {
                    currentSticky.remove();
                }
            };

            const postFormAndRefresh = async (form) => {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    redirect: 'follow',
                });
                const html = await response.text();
                replaceFromHTML(html);
                bindCartHandlers();
            };

            const bindCartHandlers = () => {
                document.querySelectorAll('.cart-card__qty').forEach((form) => {
                    if (form.dataset.bound === '1') return;
                    form.dataset.bound = '1';

                    const qtyInput = form.querySelector('input[name="quantity"]');
                    const btnMinus = form.querySelector('.cart-qty-decrease');
                    const btnPlus = form.querySelector('.cart-qty-increase');

                    let timer = null;
                    const scheduleSubmit = () => {
                        window.clearTimeout(timer);
                        timer = window.setTimeout(() => {
                            postFormAndRefresh(form).catch(() => {
                                try { form.submit(); } catch (e) {}
                            });
                        }, 120);
                    };

                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        scheduleSubmit();
                    });

                    qtyInput?.addEventListener('input', scheduleSubmit);
                    qtyInput?.addEventListener('change', scheduleSubmit);

                    btnMinus?.addEventListener('click', () => {
                        if (!qtyInput) return;
                        const current = parseInt(qtyInput.value || '1', 10);
                        if (current <= 1) {
                            const target = btnMinus.dataset.deleteTarget ? document.getElementById(btnMinus.dataset.deleteTarget) : null;
                            if (target) postFormAndRefresh(target).catch(() => target.submit());
                            return;
                        }
                        qtyInput.stepDown();
                        qtyInput.dispatchEvent(new Event('change'));
                    });

                    btnPlus?.addEventListener('click', () => {
                        if (!qtyInput) return;
                        qtyInput.stepUp();
                        qtyInput.dispatchEvent(new Event('change'));
                    });
                });
            };

            bindCartHandlers();
        })();
    </script>
@endsection
