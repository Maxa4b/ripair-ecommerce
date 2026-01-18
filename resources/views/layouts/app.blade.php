<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <!-- Legacy RIPAIR styles -->
        <link rel="stylesheet" href="{{ asset('css/critical.css') }}">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/ecommerce.css') }}">
        <link rel="stylesheet" href="{{ asset('css/cart-animations.css') }}">
        <style>
            .navbar .nav-inner {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 20px 36px;
                position: relative;
                z-index: 12000;
            }
            .navbar-links {
                display: flex;
                gap: 32px;
                align-items: center;
            }
            #hamburger {
                display: none;
            }
            .navbar-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: nowrap;
            }
            .mobile-menu {
                left: 0;
                right: 0;
                width: auto;
                margin: 0 12px;
                display: none;
                flex-direction: column;
            }
            .footer .footer-content {
                width: min(1600px, 98vw);
                margin: 0 auto;
            }
            @media (max-width: 1024px) {
                .cart-free-mini {
                    display: none !important;
                }
            }
            @media (max-width: 900px) {
                .navbar .nav-inner {
                    padding: 14px 16px;
                    gap: 12px;
                }
                .navbar-links {
                    display: none;
                }
                #hamburger {
                    display: block;
                    width: 46px;
                    height: 46px;
                    border-radius: 16px;
                    border: 1px solid rgba(15, 23, 42, 0.1);
                    background: rgba(255, 255, 255, 0.92);
                    color: #0f172a;
                    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.14);
                    backdrop-filter: blur(10px);
                    display: grid;
                    place-items: center;
                    transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
                }
                #hamburger svg {
                    width: 22px;
                    height: 22px;
                }
                #hamburger:hover {
                    box-shadow: 0 20px 42px rgba(15, 23, 42, 0.16);
                    border-color: rgba(58, 186, 252, 0.38);
                }
                #hamburger:active {
                    transform: translateY(1px) scale(0.98);
                }
                #hamburger:focus-visible {
                    outline: 3px solid rgba(58, 186, 252, 0.28);
                    outline-offset: 3px;
                }
                .menu-open #hamburger {
                    background: linear-gradient(135deg, #3abafc, #2698d8);
                    color: #fff;
                    border-color: rgba(58, 186, 252, 0.7);
                    box-shadow: 0 18px 44px rgba(3, 118, 184, 0.28);
                }
                .navbar-actions {
                    display: none;
                }
                .navbar-logo img {
                    height: 44px;
                }
                .mobile-menu {
                    position: fixed;
                    top: 96px;
                    left: 12px;
                    right: 12px;
                    margin: 0;
                    padding: 14px;
                    background: rgba(255, 255, 255, 0.96);
                    backdrop-filter: blur(12px);
                    box-shadow: 0 26px 60px rgba(15, 23, 42, 0.18);
                    z-index: 30000;
                    border-radius: 22px;
                    border: 1px solid rgba(15, 23, 42, 0.08);
                    max-height: calc(100vh - 128px);
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    overflow: hidden;
                    opacity: 0;
                    transform: translateY(-10px);
                    pointer-events: none;
                    transition: transform 0.18s ease, opacity 0.18s ease;
                }
                .mobile-menu[hidden] {
                    display: none !important;
                }
                .mobile-menu.open {
                    opacity: 1;
                    transform: translateY(0);
                    pointer-events: auto;
                    overflow-y: auto;
                }
                .mobile-menu a {
                    padding: 12px 14px;
                    border-radius: 16px;
                    border: 1px solid rgba(15, 23, 42, 0.08);
                    background: #fff;
                    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
                    font-weight: 800;
                    color: #0f172a;
                    text-decoration: none;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
                }
                .mobile-menu a::after {
                    content: '›';
                    font-size: 18px;
                    line-height: 1;
                    opacity: 0.55;
                    transform: translateY(-1px);
                }
                .mobile-menu a:hover {
                    transform: translateY(-1px);
                    border-color: rgba(58, 186, 252, 0.45);
                    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.1);
                }
                .mobile-menu form {
                    margin: 0;
                }
                .mobile-menu .mobile-menu__logout {
                    width: 100%;
                    padding: 12px 18px;
                    background: linear-gradient(135deg, #f87171, #dc2626);
                    color: #fff;
                    border: none;
                    border-radius: 28px;
                    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.22);
                    font-weight: 800;
                    font-size: 15px;
                    display: inline-flex;
                    gap: 8px;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: filter .2s, transform .2s;
                }
                .mobile-menu .mobile-menu__logout:hover {
                    filter: brightness(.92);
                    transform: translateY(-1px);
                }
                .menu-open .cart-fab,
                .menu-open .cart-free-mini,
                .menu-open .free-shipping-progress {
                    display: none !important;
                }
                .menu-open {
                    overflow: hidden;
                }
            }
        </style>
        @if(Route::is('checkout.*'))
            @php
                $paypalClientId = config('services.paypal.client_id');
                $paypalMode = config('services.paypal.mode', 'live');
            @endphp
            @if($paypalClientId)
                <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&components=buttons&disable-funding=venmo&currency=EUR&intent=capture&commit=true{{ $paypalMode === 'sandbox' ? '&debug=true' : '' }}"></script>
            @endif
        @endif

        <!-- Scripts -->
        <script defer src="{{ asset('assets/js/script.js') }}"></script>
        @vite(['resources/js/app.js'])
    </head>
    <body @if(Route::is('cart.show') || Route::is('checkout.*') || Route::is('account.*')) data-hide-cart-fab="true" @endif>
        <header class="navbar navbar--glass">
            <div class="nav-inner">
                <a class="navbar-logo" href="{{ route('home') }}">
                    <img src="{{ asset('assets/img/logo.webp') }}" alt="RIPAIR Logo">
                </a>

                <nav class="navbar-links">
                    <a href="{{ route('home') }}">Accueil</a>
                    <a href="{{ route('catalog.index') }}">Catalogue</a>
                    <a href="{{ route('cart.show') }}">Panier</a>
                    <a href="https://ripair.shop" class="nav-cta">Nos services</a>
                    {{-- <a href="{{ route('pro.apply') }}">Espace PRO</a> --}}
                </nav>

                <div class="navbar-actions">
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="btn" style="padding:12px 18px;background:linear-gradient(135deg,#3abafc,#2698d8);color:#fff;border:none;border-radius:28px;box-shadow:0 10px 24px rgba(58,186,252,0.22);">Mon compte</a>
                        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn" style="padding:12px 18px;background:linear-gradient(135deg,#f87171,#dc2626);color:#fff;border:none;border-radius:28px;box-shadow:0 10px 24px rgba(220,38,38,0.22);font-weight:800;font-size:15px;display:inline-flex;gap:8px;align-items:center;" aria-label="Déconnexion">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 7l5 5-5 5" />
                                    <path d="M9 12h10" />
                                    <path d="M5 5v14" />
                                </svg>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn" style="padding:12px 18px;background:linear-gradient(135deg,#3abafc,#2698d8);color:#fff;border:none;border-radius:28px;box-shadow:0 10px 24px rgba(58,186,252,0.22);">Connexion</a>
                        <a href="{{ route('register') }}" class="btn" style="padding:12px 18px;background:linear-gradient(135deg,#3abafc,#2698d8);color:#fff;border:none;border-radius:28px;box-shadow:0 10px 24px rgba(58,186,252,0.22);">Créer un compte</a>
                    @endauth
                </div>

                <button class="icon-btn" aria-label="Ouvrir le menu" id="hamburger" aria-controls="mobileMenu" aria-expanded="false" onclick="toggleMenu(event)">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <nav id="mobileMenu" class="mobile-menu" hidden style="display:none">
                    <a href="{{ route('catalog.index') }}">Catalogue</a>
                    {{-- <a href="{{ route('pro.apply') }}">Espace PRO</a> --}}
                    <a href="{{ route('cart.show') }}">Panier</a>
                    <a href="https://ripair.shop" class="nav-cta">Nos services</a>
                    @auth
                        <a href="{{ route('account.dashboard') }}">Mon compte</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="mobile-menu__logout" aria-label="Déconnexion">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Connexion</a>
                        <a href="{{ route('register') }}">Créer un compte</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="container" style="padding:48px 0 64px;">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        @unless(Route::is('cart.show') || Route::is('checkout.*') || Route::is('account.*'))
            @php
                try {
                    $cartService = app(\App\Services\Commerce\CartService::class);
                    $cartModel = $cartService->resolveCart(auth()->user());
                    $cartCount = $cartModel->items->sum('quantity');
                    $cartSubtotal = $cartModel->subtotal_ttc ?? 0;
                    $freeShippingMin = (float) (config('pricing.free_shipping_min_total') ?? 0);
                    $progress = $freeShippingMin > 0 ? min(100, max(0, ($cartSubtotal / $freeShippingMin) * 100)) : 0;
                    $remaining = $freeShippingMin > 0 ? max(0, $freeShippingMin - $cartSubtotal) : 0;
                } catch (\Throwable $e) {
                    $cartCount = 0;
                    $cartSubtotal = 0;
                    $freeShippingMin = 0;
                    $progress = 0;
                    $remaining = 0;
                }
            @endphp
            <button class="cart-fab" id="cartFab" data-href="{{ route('cart.show') }}" data-cart-count="{{ $cartCount }}" aria-label="Voir le panier">
                <svg viewBox="0 0 24 24" width="28" height="28" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="sr-only">Panier</span>
            </button>
            <div class="cart-toast" id="cartToast">Articles ajoutés</div>
            @if($freeShippingMin > 0)
                <div class="cart-free-mini" id="cartFreeMini"
                     data-free-shipping-min="{{ $freeShippingMin }}"
                     data-cart-total="{{ $cartSubtotal }}">
                    <div class="cart-free-mini__text">
                        @if($remaining <= 0)
                            Livraison offerte 🎉
                        @else
                            Encore {{ number_format($remaining, 2, ',', ' ') }} € pour la livraison offerte
                        @endif
                    </div>
                    <div class="cart-free-mini__bar">
                        <span style="width: {{ $progress }}%;"></span>
                    </div>
                </div>
            @endif
        @endunless

        @include('layouts.partials.footer')
        @stack('scripts')
        <script>
            // Menu mobile plein écran
            (() => {
                const menu = document.getElementById('mobileMenu');
                const button = document.getElementById('hamburger');
                if (!menu || !button) return;

                let closeTimer = null;

                const hardClose = () => {
                    window.clearTimeout(closeTimer);
                    closeTimer = null;
                    menu.style.display = 'none';
                    menu.hidden = true;
                    menu.classList.remove('open');
                    document.body.classList.remove('menu-open');
                    button.setAttribute('aria-expanded', 'false');
                };

                const setClosed = () => {
                    window.clearTimeout(closeTimer);
                    menu.classList.remove('open');
                    document.body.classList.remove('menu-open');
                    button.setAttribute('aria-expanded', 'false');
                    closeTimer = window.setTimeout(() => {
                        menu.style.display = 'none';
                        menu.hidden = true;
                    }, 220);
                };

                const setOpen = () => {
                    window.clearTimeout(closeTimer);
                    // Nettoie l'ancien display forcé éventuel
                    menu.style.removeProperty('display');
                    menu.hidden = false;
                    requestAnimationFrame(() => {
                        menu.classList.add('open');
                        document.body.classList.add('menu-open');
                        button.setAttribute('aria-expanded', 'true');
                    });
                };

                const isOpen = () => !menu.hidden && menu.classList.contains('open');

                window.toggleMenu = function (event) {
                    event?.stopPropagation?.();
                    if (isOpen()) setClosed();
                    else setOpen();
                };

                document.addEventListener('click', (event) => {
                    if (!isOpen()) return;
                    if (menu.contains(event.target) || button.contains(event.target)) return;
                    setClosed();
                });

                window.addEventListener('pagehide', hardClose);
                window.addEventListener('pageshow', hardClose);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'hidden') hardClose();
                });

                menu.addEventListener('click', (event) => {
                    const link = event.target instanceof Element ? event.target.closest('a') : null;
                    if (link && menu.contains(link)) {
                        hardClose();
                    }
                });
                menu.addEventListener('submit', hardClose);

                // Force un état fermé au chargement (évite le flash si bfcache).
                hardClose();
            })();
        </script>
    </body>
</html>
