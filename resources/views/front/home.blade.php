@extends('layouts.app')

@section('content')
    <section class="catalog-hero">
        <div class="catalog-hero__inner">
            <div>
                <p class="hero-sub" style="text-transform:uppercase; letter-spacing:0.22em; margin:0;">RIPAIR e-commerce</p>
                <h1 class="section-title" style="text-align:left; margin:10px 0 6px;">Stock pro RIPAIR, prêt à partir</h1>
                <p style="max-width:560px; line-height:1.6; color:#4b5563;">
                    Pièces contrôlées par l’atelier (coloris, compatibilité) et expédiées depuis Cestas en 24/48h. Parcours par marque ou modèle, ajoute au panier ou demande un devis en deux clics.
                </p>
                <div class="hero-buttons" style="justify-content:flex-start; margin-top:14px; gap:10px;">
                    <a href="{{ route('catalog.index') }}" class="btn">Voir le catalogue</a>
                    <a href="https://ripair.shop/devis.html" class="btn">Demander un devis</a>
                </div>
                <div class="hero-chip-row">
                    <span class="hero-chip">Couleur vérifiée</span>
                    <span class="hero-chip">Expédition 24/48h</span>
                    <span class="hero-chip">Bordereau prêt</span>
                </div>
            </div>
            <div class="catalog-hero__picture" aria-hidden="true">
                <div class="hero-badges">
                    <div class="hero-badge">
                        <span class="hero-badge__icon hero-badge__icon--blue">🚚</span>
                        <div class="hero-badge__meta">
                            <strong>Expédié 24/48h</strong>
                            <p>Depuis l’atelier de Cestas</p>
                        </div>
                    </div>
                    <div class="hero-badge">
                        <span class="hero-badge__icon hero-badge__icon--green">🎨</span>
                        <div class="hero-badge__meta">
                            <strong>Coloris vérifiés</strong>
                            <p>Mêmes références que le site public</p>
                        </div>
                    </div>
                    <div class="hero-badge">
                        <span class="hero-badge__icon hero-badge__icon--orange">📦</span>
                        <div class="hero-badge__meta">
                            <strong>Colissimo / Chronopost</strong>
                            <p>Ou Mondial Relay si besoin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .hero-badges {
            background:linear-gradient(135deg,rgba(17,133,210,0.08),rgba(255,255,255,0.9));
            border:1px solid #dfe9f6;
            border-radius:18px;
            padding:18px;
            box-shadow:0 20px 40px -30px rgba(0,0,0,0.3);
            display:grid;
            gap:12px;
        }
        .hero-badge {
            display:flex;
            gap:12px;
            align-items:center;
            padding:10px 12px;
            border-radius:12px;
            background:rgba(255,255,255,0.85);
            border:1px solid #e8f0fa;
            backdrop-filter: blur(4px);
        }
        .hero-badge__icon {
            font-size:18px;
            line-height:1;
            width:34px;
            height:34px;
            border-radius:12px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            color:#0f274d;
        }
        .hero-badge__icon--blue {background:linear-gradient(135deg,#e0f3ff,#c7e6ff);}
        .hero-badge__icon--green {background:linear-gradient(135deg,#e7f7ef,#d3f1e2);}
        .hero-badge__icon--orange {background:linear-gradient(135deg,#fff3e0,#ffe4c2);}
        .hero-badge__meta strong {display:block; color:#0f274d;}
        .hero-badge__meta p {margin:2px 0 0; color:#4b5563; font-size:14px;}
        .hero-chip-row {display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;}
        .hero-chip {
            padding:7px 10px;
            border-radius:12px;
            background:#f5f8fd;
            border:1px solid #e3ecf7;
            font-size:12px;
            color:#0f274d;
        }
        @media (max-width: 640px) {
            .hero-chip-row {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                justify-items: center;
                align-items: center;
                gap: 10px 12px;
                margin-top: 16px;
            }
            .hero-chip-row .hero-chip {
                width: 100%;
                text-align: center;
                font-size: 11.5px;
            }
            .hero-chip-row .hero-chip:nth-child(3) {
                grid-column: 1 / 3;
                justify-self: center;
                width: 78%;
            }
            .catalog-hero__inner {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 18px;
            }
            .catalog-hero .section-title,
            .catalog-hero p,
            .catalog-hero .hero-sub {
                text-align: center !important;
                margin-left: auto;
                margin-right: auto;
            }
            .hero-buttons {
                justify-content: center !important;
            }
            .catalog-hero__picture {
                width: 100%;
                max-width: 420px;
                margin: 0 auto;
            }
            .categories-head {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 12px;
            }
            .categories-head .section-title {
                text-align: center;
                width: 100%;
            }
            .categories-head .btn-ghost {
                width: auto;
                align-self: center;
            }
            .category-chip {
                justify-content: center;
                text-align: center;
            }
            .category-chip__meta {
                align-items: center;
            }
        }
        .categories-head {display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;}
        .category-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
            gap:12px;
            margin-top:12px;
        }
        .category-card {
            display:flex;
            align-items:center;
            gap:10px;
            padding:12px 14px;
            border:1px solid #e5edf7;
            border-radius:12px;
            background:#fff;
            box-shadow:0 12px 28px -26px rgba(0,0,0,0.2);
            color:#0f274d;
            text-decoration:none;
            font-weight:700;
        }
        .category-card:hover {border-color:#cfe2fa;}
        .category-row {
            display: grid;
            gap: 14px;
        }
        .category-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff, #f8fbff);
            border: 1px solid #dfe7f1;
            box-shadow: 0 12px 26px -18px rgba(15, 76, 129, 0.18);
            text-decoration: none;
            color: #0f274d;
            transition: transform .15s ease, box-shadow .15s ease, border-color .2s ease, background .2s ease;
        }
        .category-chip:hover {
            transform: translateY(-1px);
            border-color: #cfe2fa;
            box-shadow: 0 14px 30px -18px rgba(15, 76, 129, 0.25);
            background: linear-gradient(135deg, #fdfefe, #eef6ff);
        }
        .category-chip__info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .category-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1185d2, #18a8ff);
            box-shadow: 0 0 0 8px rgba(17, 133, 210, 0.12);
        }
        .category-chip__text small {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .category-chip__text strong {
            font-size: 16px;
            line-height: 1.4;
        }
        .category-chevron {
            font-size: 18px;
            color: #9ca3af;
        }
    </style>

    {{-- CATEGORIES PRINCIPALES --}}
    <section class="services categories-section">
        <div class="categories-head">
            <div>
                <h2 class="section-title" style="margin:0;">Catégories principales</h2>
            </div>
            <a href="{{ route('catalog.index') }}" class="btn btn-secondary btn-ghost">Voir tout</a>
        </div>
        <div class="category-row" style="margin-top: 12px;">
            @foreach ($families as $family)
                <a href="{{ route('catalog.index') . '?' . http_build_query(['category' => [$family->value]]) }}" class="category-chip">
                    <div class="category-chip__info">
                        <span class="category-dot"></span>
                        <div class="category-chip__text">
                            <strong>{{ $family->name }}</strong>
                        </div>
                    </div>
                    <span class="category-chevron" aria-hidden="true">›</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- NOUVEAUTÉS --}}
    <section class="services">
        <h2 class="section-title">Nouveautés arrivées au stock</h2>
        <div class="associated-wrapper" data-associated-wrapper>
            <button type="button" class="associated-nav associated-nav--prev" data-associated-prev aria-label="Faire défiler vers la gauche">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="15 18 9 12 15 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="associated-viewport">
                <div class="catalog-grid associated-grid">
                    @foreach ($newProducts as $product)
                        <a class="catalog-card" href="{{ route('products.show', ['repair' => $product->id, 'slug' => $product->slug]) }}">
                            <figure>
                                <img src="{{ $product->image_fallback }}" alt="{{ $product->title }}">
                            </figure>
                            <div>
                                <small>{{ $product->brand }} • {{ $product->model }}</small>
                                <h3>{{ $product->problem }}</h3>
                            </div>
                            <div class="price">
                                @if ($product->supplier_stock && $product->supplier_stock > 0)
                                    {{ $product->display_price }} € TTC
                                @else
                                    <span class="stock-label">Rupture de stock</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <button type="button" class="associated-nav associated-nav--next" data-associated-next aria-label="Faire défiler vers la droite">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="9 6 15 12 9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </section>

    {{-- VALEURS SÛRES --}}
    <section class="services">
        <h2 class="section-title">Valeurs sûres de l'atelier</h2>
        <div class="associated-wrapper" data-associated-wrapper>
            <button type="button" class="associated-nav associated-nav--prev" data-associated-prev aria-label="Faire défiler vers la gauche">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="15 18 9 12 15 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="associated-viewport">
                <div class="catalog-grid associated-grid">
                    @foreach ($bestSellers as $product)
                        <a class="catalog-card" href="{{ route('products.show', ['repair' => $product->id, 'slug' => $product->slug]) }}">
                            <figure>
                                <img src="{{ $product->image_fallback }}" alt="{{ $product->title }}">
                            </figure>
                            <div>
                                <small>{{ $product->brand }} • {{ $product->model }}</small>
                                <h3>{{ $product->problem }}</h3>
                            </div>
                            <div class="price">
                                @if ($product->supplier_stock && $product->supplier_stock > 0)
                                    {{ $product->display_price }} € TTC
                                @else
                                    <span class="stock-label">Rupture de stock</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <button type="button" class="associated-nav associated-nav--next" data-associated-next aria-label="Faire défiler vers la droite">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="9 6 15 12 9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="services">
        <div class="cta-lite cta-help">
            <div class="cta-text">
                <div class="cta-kicker">Besoin d’un avis express</div>
                <h3>Un doute sur une variante ou une couleur&nbsp;?</h3>
                <p>Contacte l’atelier ou demande un devis rapide, on te répond dans la journée.</p>
                <div class="cta-actions">
                    <a class="btn btn-secondary btn-ghost" href="https://ripair.shop/contact.html">Parler à un expert</a>
                    <a class="btn btn-highlight" href="https://ripair.shop/devis.html">Demander un devis</a>
                </div>
            </div>
            <div class="cta-card">
                <div class="cta-chip">Atelier RIPAIR</div>
                <p class="cta-note">Guidage produit, dispo couleur, délais & options.</p>
                <div class="cta-pill-grid">
                    <div class="cta-pill">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"></circle>
                                <polyline points="12 7 12 12 15 15"></polyline>
                            </svg>
                        </span>
                        Réponse <strong>&lt; 24h</strong>
                    </div>
                    <div class="cta-pill">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="4"></circle>
                                <line x1="12" y1="2" x2="12" y2="6"></line>
                                <line x1="12" y1="18" x2="12" y2="22"></line>
                                <line x1="2" y1="12" x2="6" y2="12"></line>
                                <line x1="18" y1="12" x2="22" y2="12"></line>
                            </svg>
                        </span>
                        Recommandation variante
                    </div>
                    <div class="cta-pill">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <line x1="8" y1="13" x2="16" y2="13"></line>
                                <line x1="8" y1="17" x2="14" y2="17"></line>
                            </svg>
                        </span>
                        Estimation claire et rapide
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .cta-help {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            padding: 22px 24px;
            border-radius: 18px;
            background: radial-gradient(circle at 10% 10%, #ecf7ff 0, #f4f9ff 40%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            box-shadow: 0 18px 50px -24px rgba(16, 81, 164, 0.35);
            align-items: center;
        }
        .cta-text h3 {
            margin: 4px 0 6px;
            font-size: 22px;
            color: #0b1f4f;
        }
        .cta-text p {
            margin: 0 0 14px;
            color: #475569;
        }
        .cta-kicker {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0ea5e9;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: .3px;
        }
        .cta-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .btn-ghost {
            background: #f8fafc;
            color: #0b1f4f;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 22px -18px rgba(0,0,0,0.3);
        }
        .btn-highlight {
            background: linear-gradient(135deg, #38bdf8, #0ea5e9);
            box-shadow: 0 12px 30px -14px rgba(14,165,233,0.55);
            border: none;
        }
        .cta-card {
            background: linear-gradient(145deg, #f4f9ff, #e9f2ff);
            color: #0b1f4f;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 18px 40px -24px rgba(15, 76, 129, 0.25);
            border: 1px solid #d7e3f4;
        }
        .cta-chip {
            display: inline-flex;
            padding: 7px 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #0b1f4f;
            font-weight: 800;
            font-size: 12px;
            letter-spacing: .3px;
            margin-bottom: 10px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
        }
        .cta-note {
            margin: 0 0 12px;
            font-size: 15px;
            color: #1f2c4f;
        }
        .cta-pill-grid {
            display: grid;
            gap: 10px;
        }
        .cta-pill {
            background: #fff;
            border: 1px solid #d7e3f4;
            border-radius: 14px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0b1f4f;
            font-size: 14px;
            box-shadow: 0 12px 28px -20px rgba(15, 76, 129, 0.2);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .cta-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 32px -20px rgba(15, 76, 129, 0.28);
        }
        .cta-pill span {
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #e0f2fe, #e8ddff);
            font-size: 13px;
            color: #0b1f4f;
        }
        @media (max-width: 640px) {
            .cta-help {
                text-align: center;
                grid-template-columns: 1fr;
            }
            .cta-text h3,
            .cta-text p {
                text-align: center;
            }
            .cta-actions {
                justify-content: center;
            }
            .cta-card {
                text-align: center;
            }
            .cta-pill-grid {
                grid-template-columns: 1fr;
            }
            .cta-pill {
                justify-content: center;
            }
        }
        @media (max-width: 640px) {
            .stock-label {
                display: inline-block;
                font-size: 13px;
                color: #b91c1c;
            }
        }
    </style>
@endsection
