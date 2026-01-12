@extends('layouts.app')

@php
    $colorMap = [
        'black' => 'Noir',
        'white' => 'Blanc',
        'blue' => 'Bleu',
        'dark blue' => 'Bleu marine',
        'light blue' => 'Bleu clair',
        'green' => 'Vert',
        'midnight green' => 'Vert nuit',
        'red' => 'Rouge',
        'yellow' => 'Jaune',
        'purple' => 'Violet',
        'pink' => 'Rose',
        'orange' => 'Orange',
        'gray' => 'Gris',
        'grey' => 'Gris',
        'silver' => 'Argent',
        'gold' => 'Or',
        'beige' => 'Beige',
        'brown' => 'Marron',
        'transparent' => 'Transparent',
        'midnight' => 'Bleu nuit',
    ];

    $rawColor = strtolower(trim((string) $product->color));
    $translatedColor = $rawColor !== '' ? ($colorMap[$rawColor] ?? ucfirst($rawColor)) : 'Standard';
    $qualityToken = strtolower(trim((string) $product->component_brand));
    $isOriginal = str_contains($qualityToken, 'oem') || str_contains($qualityToken, 'original') || str_contains($qualityToken, 'origine') || str_contains($qualityToken, 'genuine');
    $componentType = $isOriginal ? 'Origine' : 'Compatible';
@endphp

@section('content')
    <div class="product-detail">
        <div class="product-gallery">
            <img src="{{ $product->image_fallback }}" alt="{{ $product->title }}">
        </div>
        <div>
            <p class="hero-sub" style="margin:0;">{{ $product->category }} • {{ $product->brand }}</p>
            <h1 style="margin:6px 0 12px;">{{ $product->problem }}</h1>
            <p>{{ $product->model }}</p>

            <div style="margin:24px 0;">
                @if ($product->supplier_stock && $product->supplier_stock > 0)
                    <strong style="font-size:2rem;">{{ $product->display_price }} € TTC</strong>
                @else
                    <strong style="font-size:1.6rem;color:var(--blue);">Rupture de stock</strong>
                @endif
            </div>

            @if ($product->supplier_stock && $product->supplier_stock > 0)
                <form method="POST" action="{{ route('cart.store') }}"
                      class="add-to-cart-form js-add-to-cart"
                      data-product-name="{{ $product->problem }}"
                      data-product-image="{{ $product->image_fallback }}"
                      data-product-price="{{ $product->computed_price }}">
                    @csrf
                    <input type="hidden" name="repair_id" value="{{ $product->id }}">
                    <div class="quantity-control" data-qty>
                        <button type="button" aria-label="Diminuer" onclick="this.nextElementSibling.stepDown()">−</button>
                        <input type="number" name="quantity" value="1" min="1" aria-label="Quantité">
                        <button type="button" aria-label="Augmenter" onclick="this.previousElementSibling.stepUp()">+</button>
                    </div>
                    <button type="submit" class="btn">Ajouter au panier</button>
                </form>
            @else
                <div class="add-to-cart-form">
                    <div class="quantity-control" data-qty>
                        <button type="button" aria-label="Diminuer" disabled>−</button>
                        <input type="number" value="0" min="0" aria-label="Quantité" disabled>
                        <button type="button" aria-label="Augmenter" disabled>+</button>
                    </div>
                    <a href="{{ url('/contact.html') }}" class="btn btn-secondary">Me prévenir</a>
                </div>
            @endif

            <ul class="info-list" style="margin-top:24px;">
                <li><strong>Référence fournisseur</strong><br>{{ $product->supplier_ref ?? 'N/A' }}</li>
                <li><strong>Couleur</strong><br>{{ $translatedColor }}</li>
                <li><strong>Type de pièce</strong><br>{{ $componentType }}</li>
            </ul>
        </div>
    </div>

    @php
        $catalogDescription = trim((string) ($catalogProduct?->description ?? $catalogProduct?->rhc_generated_description ?? ''));
    @endphp

    @if ($catalogDescription !== '')
        <section class="services product-description-section">
            <h2 class="section-title">Description</h2>
            <div class="product-description-card">
                <p class="product-description-text">{{ $catalogDescription }}</p>
            </div>
        </section>
    @endif

    <section class="services">
        <h2 class="section-title">Pièces associées</h2>
        <div class="associated-wrapper" data-associated-wrapper>
            <button type="button" class="associated-nav associated-nav--prev" data-associated-prev aria-label="Faire défiler vers la gauche">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="15 18 9 12 15 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="associated-viewport">
                <div class="catalog-grid associated-grid">
                    @foreach ($related as $item)
                        <a class="catalog-card" href="{{ route('products.show', ['repair' => $item->id, 'slug' => $item->slug]) }}">
                            <figure>
                                <img src="{{ $item->image_fallback }}" alt="{{ $item->title }}">
                            </figure>
                            <div>
                                <small>{{ $item->category }} • {{ $item->brand }}</small>
                                <h3>{{ $item->problem }}</h3>
                                <p>{{ $item->model }}</p>
                            </div>
                            <div class="price">
                                @if ($item->supplier_stock && $item->supplier_stock > 0)
                                    {{ $item->display_price }} € TTC
                                @else
                                    Rupture de stock
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
@endsection
