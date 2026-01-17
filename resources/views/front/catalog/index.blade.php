@extends('layouts.app')

@php($active = collect($activeFilters))
@php($activeCount = collect($activeFilters)
    ->except(['sort', 'page'])
    ->reduce(function ($carry, $value) {
        if (is_array($value)) {
            return $carry + collect($value)
                ->flatten()
                ->filter(function ($v) {
                    if (is_string($v)) {
                        return trim($v) !== '';
                    }
                    return !is_null($v);
                })
                ->unique()
                ->count();
        }
        if (is_string($value)) {
            return $carry + (trim($value) !== '' ? 1 : 0);
        }
        return $carry + (!is_null($value) ? 1 : 0);
    }, 0))
@php($requestedSort = request('sort', 'relevance'))
@php($currentSortValue = $requestedSort === 'latest' ? 'relevance' : $requestedSort)

@section('content')
    <style>
	        @media (max-width: 1024px) {
	            body {
	                background: linear-gradient(180deg, #f5f7fb 0%, #eef2ff 40%, #ffffff 100%);
	                overflow-x: hidden;
	            }
	            main.container {
	                padding: 0 0 110px;
	                box-sizing: border-box;
	                width: 100%;
	                max-width: 100%;
	                margin: 0;
	            }
	            .catalog-layout > * {
	                min-width: 0;
	            }
	            .catalog-layout {
	                display: grid;
	                grid-template-columns: 1fr;
	                gap: 16px;
	                padding: 0 12px 110px;
	                max-width: 100%;
	                width: 100%;
	                margin: 0 auto;
	                box-sizing: border-box;
	                overflow-x: hidden;
	            }
            .catalog-meta {
                position: relative;
                z-index: auto;
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 10px;
                padding: 0 12px;
                margin: 6px auto 10px;
                width: 100%;
                box-sizing: border-box;
                background: transparent;
                backdrop-filter: none;
                border-radius: 0;
                border: none;
                box-shadow: none;
            }
            .catalog-meta__title {
                order: 0;
                text-align: left;
            }
            .catalog-meta__actions {
                order: 1;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                width: 100%;
                box-sizing: border-box;
            }
            .catalog-meta__actions .custom-select-wrapper {
                margin: 0;
            }
            .catalog-meta__actions .filter-toggle {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 0.65rem 1.25rem;
                border: 1.5px solid rgba(58, 186, 252, 0.5);
                border-radius: 999px;
                background: linear-gradient(180deg, #e6f5ff, #fff);
                color: var(--blue);
                font-weight: 600;
                box-shadow: 0 18px 34px rgba(3, 118, 184, 0.16);
                transition: box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
                width: 100%;
                justify-content: center;
                box-sizing: border-box;
            }
            .catalog-meta__actions .filter-toggle:hover {
                box-shadow: 0 24px 44px rgba(3, 118, 184, 0.2);
                border-color: rgba(3, 118, 184, 0.65);
            }
            .catalog-meta__actions .filter-toggle:active {
                transform: translateY(1px);
            }
            .catalog-meta__actions .filter-toggle svg {
                width: 18px;
                height: 18px;
            }
            .catalog-meta__actions .filter-badge {
                background: linear-gradient(135deg, #3abafc, #2698d8);
                color: #fff;
                padding: 5px 10px;
                border-radius: 12px;
                font-size: 12px;
                line-height: 1;
            }
            .filter-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.3);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.18s ease;
                z-index: 39990;
            }
            .filter-backdrop.is-visible {
                opacity: 1;
                pointer-events: auto;
            }
            .filter-card {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                margin: 0 10px;
                max-width: 740px;
                max-height: calc(100vh - 92px);
                overflow: hidden;
                width: auto;
                z-index: 40000;
                transform: translateY(10px);
                opacity: 0;
                pointer-events: none;
                transition: all 0.2s ease;
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 20px 46px rgba(15, 23, 42, 0.18);
                display: flex;
                flex-direction: column;
            }
            .filter-card.is-open {
                transform: translateY(0);
                opacity: 1;
                pointer-events: auto;
            }
            .filter-card form {
                display: grid;
                gap: 14px;
                padding: 0 14px 18px;
                padding-bottom: max(18px, env(safe-area-inset-bottom, 16px));
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                flex: 1;
            }
            .filter-card form button[type="submit"] {
                width: 100%;
                border: none;
                border-radius: 14px;
                padding: 14px;
                font-weight: 800;
                background: linear-gradient(135deg, #3abafc, #2698d8);
                color: #fff;
                box-shadow: 0 12px 28px rgba(58, 186, 252, 0.2);
            }
            .filter-mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                position: sticky;
                top: 0;
                padding: 14px 14px;
                margin: 10px 12px 12px;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.94);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(15, 23, 42, 0.06);
                z-index: 50;
                box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
            }
            .filter-mobile-header h2 {
                margin: 0;
                font-size: 18px;
                letter-spacing: 0.01em;
                color: #3abafc;
            }
            .filter-mobile-header__actions {
                display: inline-flex;
                align-items: center;
                gap: 10px;
            }
            .filter-clear {
                border: none;
                background: linear-gradient(135deg, #3abafc, #2698d8);
                color: #fff;
                padding: 9px 12px;
                border-radius: 999px;
                font-weight: 900;
                font-size: 13px;
                line-height: 1;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                white-space: nowrap;
                box-shadow: 0 12px 22px rgba(58, 186, 252, 0.22);
                transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
            }
            .filter-clear:hover {
                filter: brightness(1.02);
                box-shadow: 0 16px 28px rgba(58, 186, 252, 0.28);
            }
            .filter-clear:active {
                transform: translateY(1px);
            }
            .filter-search {
                border: none;
                background: rgba(58, 186, 252, 0.14);
                color: #0376b8;
                padding: 8px;
                border-radius: 10px;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.15s ease, background 0.2s ease;
            }
            .filter-search:hover {
                background: rgba(58, 186, 252, 0.2);
            }
            .filter-search:active {
                transform: translateY(1px);
            }
            .filter-mobile-header [data-filter-close] {
                border: none;
                background: rgba(15, 23, 42, 0.06);
                color: #0f172a;
                padding: 8px;
                border-radius: 10px;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .filter-desktop-title {
                display: none;
            }
            .filter-group h4 {
                color: #3abafc;
            }
            .custom-select-wrapper {
                width: 100%;
            }
            .custom-select {
                width: 100%;
            }
            .custom-select.is-enhanced .custom-select__trigger {
                width: 100%;
                justify-content: center;
                box-sizing: border-box;
            }
            .custom-select.is-enhanced .custom-select__dropdown {
                left: 0;
                right: 0;
                min-width: 0;
                width: 100%;
                transform: none;
                margin: 0 auto;
            }
            .chip-list,
            .problem-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 10px;
            }
            .problem-list {
                max-height: 260px;
                overflow-y: auto;
                padding-right: 6px;
            }
	            .chip-list label.filter-pill,
	            .problem-list label.filter-pill {
	                position: relative;
	                display: inline-flex;
	                align-items: center;
	                justify-content: center;
	                width: 100%;
	                padding: 8px 12px;
	                border-radius: 14px;
	                border: 1px solid rgba(17, 17, 17, 0.1);
	                background: #fff;
	                font-weight: 600;
	                text-align: center;
	                cursor: pointer;
	                transition: border 0.2s ease, box-shadow 0.2s ease;
	            }
	            .chip-list label.filter-pill span,
	            .problem-list label.filter-pill span {
	                display: block;
	                width: 100%;
	                background: transparent;
	                padding: 0;
	                border-radius: 0;
	            }
	            .chip-list label.filter-pill input[type="checkbox"],
	            .problem-list label.filter-pill input[type="checkbox"] {
	                position: absolute;
	                inset: 0;
	                opacity: 0;
	                pointer-events: none;
	            }
	            .chip-list label.filter-pill:has(input[type="checkbox"]:checked),
	            .problem-list label.filter-pill:has(input[type="checkbox"]:checked) {
	                border-color: #3abafc;
	                background: rgba(58, 186, 252, 0.08);
	                box-shadow: 0 8px 18px rgba(17, 17, 17, 0.08);
	            }
	            .chip-list label.filter-pill input[type="checkbox"]:checked + span,
	            .problem-list label.filter-pill input[type="checkbox"]:checked + span {
	                color: #0376b8;
	                font-weight: 600;
	                background: transparent;
	                padding: 0;
	                border-radius: 0;
	            }
	            .chip-list label.filter-pill:hover,
	            .problem-list label.filter-pill:hover {
	                border-color: rgba(58, 186, 252, 0.4);
	                box-shadow: 0 8px 18px rgba(17, 17, 17, 0.08);
	            }
            .product-card-list {
                --catalog-card-min: 190px;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(var(--catalog-card-min), 1fr));
                gap: 12px;
                padding: 0 8px;
                width: 100%;
                box-sizing: border-box;
            }
            .catalog-card {
                border-radius: 18px;
                border: 1px solid rgba(15, 23, 42, 0.08);
                background: #fff;
                box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
                overflow: hidden;
                padding: 12px 12px 10px;
                box-sizing: border-box;
            }
            .catalog-card__main {
                display: grid;
                grid-template-columns: 110px 1fr;
                column-gap: 12px;
                row-gap: 6px;
                padding: 6px 4px 4px;
                align-items: center;
                text-decoration: none;
                width: 100%;
            }
            .catalog-card figure {
                grid-row: 1 / span 3;
                grid-column: 1;
                width: 100%;
                height: clamp(120px, 42vw, 150px);
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
                border-radius: 14px;
                overflow: hidden;
                border: 1px solid rgba(15, 23, 42, 0.06);
            }
	            .catalog-card figure img {
	                width: 100%;
	                height: 100%;
	                object-fit: contain;
	                max-height: 100%;
	            }
            .catalog-card small {
                display: inline-flex;
                font-size: 12px;
                color: #6b7280;
            }
            .catalog-card h3 {
                font-size: 15.5px;
                margin: 0;
                color: #0f172a;
                text-align: left;
                font-weight: 800;
            }
            .catalog-card p {
                display: block;
                margin: 0;
                color: #4b5563;
                font-size: 13px;
            }
            .catalog-card .price {
                display: inline-flex;
                align-items: baseline;
                gap: 6px;
                font-size: 15px;
                font-weight: 800;
                color: #0f172a;
            }
            .catalog-card__actions {
                display: flex;
                margin-top: 6px;
            }
            .catalog-card__actions .btn,
            .catalog-card__actions.btn {
                display: inline-flex;
                width: 100%;
                justify-content: center;
                padding: 12px;
            }
            .catalog-card__actions .btn {
                background: linear-gradient(135deg, #3abafc, #2698d8);
                border: none;
                color: #fff;
            }
            .catalog-card__actions.btn {
                background: #dce4ed;
                color: #0f172a;
                border: 1px solid #cbd5e1;
            }
            .catalog-card__quickadd {
                display: none;
            }
            .free-shipping-progress {
                position: fixed;
                left: max(10px, env(safe-area-inset-left, 10px));
                right: max(10px, env(safe-area-inset-right, 10px));
                bottom: max(14px, env(safe-area-inset-bottom, 12px));
                z-index: 35000;
                box-shadow: 0 18px 30px rgba(15, 23, 42, 0.16);
                padding-bottom: max(12px, env(safe-area-inset-bottom, 10px));
                width: calc(100% - max(20px, env(safe-area-inset-left, 10px) + env(safe-area-inset-right, 10px)));
                box-sizing: border-box;
            }
            .filters-open .free-shipping-progress,
            .filters-open .cart-fab,
            .filters-open .cart-toast {
                opacity: 0;
                pointer-events: none;
            }
            body.filters-open {
                overflow: hidden;
            }
        }
	        @media (min-width: 1025px) {
	            .catalog-filter-toggle,
	            .filter-backdrop,
	            .filter-mobile-header {
	                display: none;
	            }
                .catalog-card__quickadd,
                .catalog-card__quickadd-plus {
                    display: none !important;
                }
	            .catalog-meta__title {
	                order: 1;
	                text-align: left;
	            }
	            .catalog-meta__actions {
	                order: 2;
	                display: block;
	                width: auto;
	            }
	        }
            /* Hors PC : design "mobile" des cartes */
	        @media (max-width: 1024px) {
	            .filter-card form button[type="submit"],
	            .filter-card form .filter-apply {
	                display: none;
	            }
	            .catalog-card__actions {
	                display: none;
	            }
	            .catalog-card__main {
	                display: flex;
	                flex-direction: column;
	                gap: 8px;
	                padding: 0;
	            }
	            .catalog-card figure {
	                grid-row: auto;
	                grid-column: auto;
	                width: 100%;
	                height: auto;
	                aspect-ratio: 1 / 1;
	            }
	            .catalog-card figure img {
	                display: block;
	                width: auto;
	                height: auto;
	                max-width: 64%;
	                max-height: 64%;
	                margin: 0 auto;
	            }
	            .catalog-card__main small,
	            .catalog-card__main p {
	                display: none;
	            }
	            .catalog-card__main h3 {
	                display: -webkit-box;
	                -webkit-box-orient: vertical;
	                -webkit-line-clamp: 2;
	                overflow: hidden;
	                line-height: 1.25;
	            }
	            .catalog-card,
	            .catalog-card__main,
	            .catalog-card__main > div,
	            .catalog-card__main h3 {
	                min-width: 0;
	            }
	        }
	        /* Tablettes : 2 colonnes jusqu'à 737px */
	        @media (min-width: 541px) and (max-width: 737px) {
	            .product-card-list {
	                grid-template-columns: repeat(2, minmax(0, 1fr));
		            }
		        }
	            /* Tablettes (iPad) : 3 colonnes au-delà */
		        @media (min-width: 738px) and (max-width: 1024px) {
		            .product-card-list {
		                grid-template-columns: repeat(3, minmax(0, 1fr));
		            }
		        }
	        @media (max-width: 540px) {
	            .catalog-layout {
	                padding-left: 0;
	                padding-right: 0;
	            }
	            .catalog-card__main {
	                grid-template-columns: 1fr;
	                gap: 8px;
	                padding: 0;
	            }
	            .catalog-card figure {
	                grid-row: auto;
	                grid-column: auto;
	                width: 100%;
	                height: auto;
	                aspect-ratio: 1 / 1;
	                border-radius: 14px;
	            }
	            .catalog-card figure img {
	                width: auto;
	                height: auto;
	                max-width: 64%;
	                max-height: 64%;
	                object-fit: contain;
	            }
	            .catalog-card,
	            .catalog-card__main,
	            .catalog-card__main > div,
	            .catalog-card__main h3 {
	                min-width: 0;
	            }
	            .product-card-list {
	                grid-template-columns: repeat(2, minmax(0, 1fr));
	                --catalog-card-min: 0px;
	                gap: 10px;
	                padding: 0 12px;
	                width: 100%;
	                margin: 0 auto;
	                box-sizing: border-box;
	            }
	            .chip-list,
	            .problem-list {
	                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
	            }
	            .catalog-card h3 {
	                font-size: 15px;
	            }
	            .catalog-card {
                    position: relative;
	                padding: 7px 7px 9px;
	                border-radius: 16px;
	                box-shadow: 0 12px 22px rgba(15, 23, 42, 0.1);
	            }
	            .catalog-card__actions {
	                display: block !important;
                    position: absolute;
                    right: 10px;
                    bottom: 10px;
                    z-index: 4;
                    margin: 0;
                    padding: 0;
                    background: transparent;
                    border: none;
                    width: auto;
                }
                .catalog-card > span.catalog-card__actions {
                    display: none !important;
                }
                .catalog-card__actions .btn {
                    display: none !important;
                }
                .catalog-card__quickadd {
                    display: grid;
                    place-items: center;
                    width: 38px;
                    height: 38px;
                    border-radius: 14px;
                    border: 1px solid rgba(255, 255, 255, 0.35);
                    background: linear-gradient(135deg, #3abafc, #2698d8);
                    color: #fff;
                    box-shadow: 0 16px 30px rgba(3, 118, 184, 0.26);
                    cursor: pointer;
                    touch-action: manipulation;
                    position: relative;
                    overflow: visible;
                    transition: transform 0.15s ease, filter 0.2s ease, box-shadow 0.2s ease;
                }
                .catalog-card__quickadd-plus {
                    position: absolute;
                    top: -10px;
                    right: -10px;
                    width: 20px;
                    height: 20px;
                    border-radius: 999px;
                    background: rgba(255, 255, 255, 0.98);
                    color: #0376b8;
                    display: grid;
                    place-items: center;
                    font-weight: 1000;
                    font-size: 16px;
                    line-height: 1;
                    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
                    border: 1px solid rgba(255, 255, 255, 0.6);
                }
                .catalog-card__quickadd:hover {
                    filter: brightness(1.02);
                    box-shadow: 0 18px 36px rgba(3, 118, 184, 0.3);
                }
                .catalog-card__quickadd:active {
                    transform: translateY(1px) scale(0.98);
                }
                .catalog-card__quickadd:focus-visible {
                    outline: 3px solid rgba(58, 186, 252, 0.3);
                    outline-offset: 3px;
                }
                .catalog-card__quickadd svg {
                    width: 20px;
                    height: 20px;
                }
	            .catalog-card__main .price {
                    display: flex;
                    width: 100%;
                    justify-content: flex-start;
                    align-items: baseline;
                    text-align: left;
                    justify-self: start;
                    margin-top: 6px;
                }
            .catalog-card__main small {
                display: none;
            }
            .catalog-card__main h3 {
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
                line-height: 1.25;
            }
	            .catalog-meta__actions {
	                gap: 8px;
	            }
	            .catalog-meta {
		                top: auto;
		                padding: 10px;
		                width: calc(100% - 10px);
		            }
	        }
	        @media (max-width: 420px) {
	            .catalog-meta {
	                top: auto;
	            }
		            .product-card-list {
	                padding: 0 10px;
	            }
            .cart-fab {
                right: max(10px, env(safe-area-inset-right, 10px));
            }
        }
        /* Phones étroits : cartes pleine largeur */
        /* Desktop : garder le bandeau livraison offerte collé en bas */
        @media (min-width: 1025px) {
            .free-shipping-progress {
                position: fixed;
                left: 50%;
                right: auto;
                bottom: 18px;
                transform: translateX(-50%);
                width: min(520px, calc(100% - 48px));
                margin: 0;
                z-index: 35000;
                box-sizing: border-box;
            }
        }
        @media (max-width: 540px) {
            .catalog-card {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .catalog-layout {
                width: 100%;
                max-width: 100%;
                padding-left: 0;
                padding-right: 0;
                margin: 0;
                box-sizing: border-box;
            }
	        }
    </style>
    <div class="catalog-layout">
        <aside class="filter-card" data-filter-panel id="mobileFilters">
            <div class="filter-mobile-header">
                <div>
                    <h2>Filtres</h2>
                </div>
                <div class="filter-mobile-header__actions">
                    <button type="button" class="filter-clear" data-filter-clear aria-label="Effacer tous les filtres">
                        Tout effacer
                    </button>
                    <button type="submit" class="filter-search" form="catalogFilterForm" aria-label="Rechercher">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                    <button type="button" data-filter-close aria-label="Fermer les filtres">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <h2 class="filter-desktop-title">Affiner la recherche</h2>
            <form method="GET" id="catalogFilterForm" class="filter-form" data-category="{{ $category }}" data-clear-url="{{ url()->current() }}">
                <input type="hidden" name="sort" value="{{ $currentSortValue }}">
                <div class="filter-group">
                    <h4>Recherche</h4>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Référence, modèle, etc.">
                </div>
                <div class="filter-group">
                    <h4>Catégorie</h4>
                    <div class="chip-list" data-categories>
                        @foreach (($filters['categories'] ?? []) as $categoryName)
                            <label class="filter-pill">
                                <input type="checkbox" name="category[]" value="{{ $categoryName }}" @checked(in_array($categoryName, (array) $active->get('category')))>
                                <span>{{ $categoryName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="filter-group">
                    <h4>Marque</h4>
                    <div class="chip-list">
                        @foreach ($filters['brands'] as $brand)
                            <label class="filter-pill">
                                <input type="checkbox" name="brand[]" value="{{ $brand }}" @checked(in_array($brand, (array) $active->get('brand')))>
                                <span>{{ $brand }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="filter-group">
                    <h4>Modèle</h4>
                    <div class="chip-list" data-models>
                        @foreach ($filters['models'] as $modelName)
                            <label class="filter-pill">
                                <input type="checkbox" name="model[]" value="{{ $modelName }}" @checked(in_array($modelName, (array) $active->get('model')))>
                                <span>{{ $modelName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="filter-group">
                    <h4>Problème</h4>
                    <div class="problem-list" data-problems>
                        @foreach ($filters['problems'] as $problem)
                            <label class="filter-pill">
                                <input type="checkbox" name="problem[]" value="{{ $problem }}" @checked(in_array($problem, (array) $active->get('problem')))>
                                <span>{{ $problem }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn filter-apply" style="width:100%;margin-top:1rem;">Appliquer</button>
            </form>
        </aside>
        <div class="filter-backdrop" data-filter-backdrop></div>

        <section>
            <div class="catalog-meta">
                <div class="catalog-meta__title">
                    <p class="hero-sub" style="margin:0;">Notre catalogue</p>
                    @if($category)
                        <h2 style="margin:6px 0;">{{ $category }}</h2>
                    @endif
                </div>
                @php($sortOptions = [
                    'relevance' => 'Pertinence',
                    'price_asc' => 'Prix croissant',
                    'price_desc' => 'Prix decroissant',
                ])
                @php($currentSortLabel = $sortOptions[$currentSortValue] ?? $sortOptions['relevance'])
                <div class="catalog-meta__actions">
                    <button type="button" class="filter-toggle catalog-filter-toggle" data-filter-toggle aria-expanded="false" aria-controls="mobileFilters">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <polyline points="3 12 9 12 21 12"></polyline>
                            <polyline points="3 18 14 18 21 18"></polyline>
                        </svg>
                        <span>Filtres</span>
                        <span class="filter-badge" data-filter-badge aria-label="{{ $activeCount }} filtre(s) actif(s)" @if($activeCount <= 0) style="display:none;" @endif>{{ $activeCount }}</span>
                    </button>
                    <form class="custom-select-wrapper">
                    @foreach (request()->except('sort') as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <div class="custom-select" data-custom-select>
                        <select name="sort">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" @selected($currentSortValue === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="custom-select__trigger" data-select-trigger aria-haspopup="listbox" aria-expanded="false">
                            <span data-select-label>{{ $currentSortLabel }}</span>
                            <svg viewBox="0 0 14 10" aria-hidden="true" focusable="false">
                                <path d="M2 2l5 6 5-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                        <div class="custom-select__dropdown" data-select-dropdown role="listbox">
                            @foreach ($sortOptions as $value => $label)
                                <button type="button"
                                        class="custom-select-option @if($currentSortValue === $value) is-active @endif"
                                        data-select-option
                                        data-value="{{ $value }}"
                                        role="option"
                                        aria-selected="{{ $currentSortValue === $value ? 'true' : 'false' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <noscript>
                        <button class="btn" style="margin-top:0.5rem;">Trier</button>
                    </noscript>
	                </form>
                </div>
	            </div>

            <div class="product-card-list">
                @foreach ($products as $product)
                    <article class="catalog-card">
                        <a class="catalog-card__main" href="{{ route('products.show', ['repair' => $product->id, 'slug' => $product->slug]) }}">
                            <figure>
                                <img src="{{ $product->image_fallback }}" alt="{{ $product->title }}">
                            </figure>
                            <div>
                                <small>{{ $product->category }} · {{ $product->brand }}</small>
                                @php($prefix = collect([$product->brand, $product->model])->filter()->implode(' '))
                                @php($fullName = trim(($prefix ? $prefix.' - ' : '').((string) $product->problem)))
                                <h3>{{ $fullName !== '' ? $fullName : $product->title }}</h3>
                            </div>
                            <div class="price">
                                @if ($product->supplier_stock && $product->supplier_stock > 0)
                                    {{ $product->display_price }} € TTC
                                @else
                                    <span class="badge badge-warning">Rupture de stock</span>
                                @endif
                            </div>
                        </a>
                        @if ($product->supplier_stock && $product->supplier_stock > 0)
                            <form method="POST" action="{{ route('cart.store') }}"
                                  class="catalog-card__actions js-add-to-cart"
                                  data-product-name="{{ $product->problem }}"
                                  data-product-image="{{ $product->image_fallback }}"
                                  data-product-price="{{ $product->computed_price }}">
                                @csrf
                                <input type="hidden" name="repair_id" value="{{ $product->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button class="catalog-card__quickadd" type="submit" aria-label="Ajouter au panier" title="Ajouter au panier">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M6 6h15l-1.5 8.5a2 2 0 0 1-2 1.5H8.2a2 2 0 0 1-2-1.6L5 2H2"/>
                                        <circle cx="9" cy="20" r="1"/>
                                        <circle cx="18" cy="20" r="1"/>
                                    </svg>
                                    <span class="catalog-card__quickadd-plus" aria-hidden="true">+</span>
                                </button>
                                <button class="btn" type="submit">Ajouter au panier</button>
                            </form>
                        @else
                            <span class="btn btn-secondary catalog-card__actions">Indisponible</span>
                        @endif
                    </article>
                @endforeach
                @if ($products->isEmpty())
                    <p>Aucun produit trouvé.</p>
                @endif
            </div>

            <div style="margin-top:2rem;" data-pagination>
                {{ $products->links() }}
            </div>

            @php($freeShippingMin = (float) (config('pricing.free_shipping_min_total') ?? 0))
            @php($shippingSurcharge = (float) (config('pricing.shipping_surcharge') ?? 0))
            @php($allowFreeShipping = $shippingSurcharge <= 0)
            @php($cartService = app(\App\Services\Commerce\CartService::class))
            @php($resolvedCart = $cartService ? $cartService->resolveCart(auth()->user()) : null)
            @php($cartTotal = $resolvedCart?->subtotal_ttc ?? 0)
            @php($progress = $freeShippingMin > 0 ? min(100, max(0, ($cartTotal / $freeShippingMin) * 100)) : 0)
            @php($remaining = $freeShippingMin > 0 ? max(0, $freeShippingMin - $cartTotal) : 0)
            @if($allowFreeShipping && $freeShippingMin > 0)
                <div class="free-shipping-progress" id="freeShippingBanner" data-free-shipping-min="{{ $freeShippingMin }}" data-cart-total="{{ $cartTotal }}">
                    <div class="free-shipping-text" data-free-text>
                        @if($remaining <= 0)
                            Livraison offerte sur votre panier !
                        @else
                            Plus que {{ number_format($remaining, 2, ',', ' ') }} € pour la livraison offerte
                        @endif
                    </div>
                    <div class="free-shipping-bar">
                        <span data-free-bar style="width: {{ $progress }}%;"></span>
                    </div>
                </div>
            @endif
        </section>
    </div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-filter-toggle]');
            const panel = document.querySelector('[data-filter-panel]');
            const backdrop = document.querySelector('[data-filter-backdrop]');
            const closes = document.querySelectorAll('[data-filter-close]');
            const clearBtn = document.querySelector('[data-filter-clear]');
            const body = document.body;
            const html = document.documentElement;
            const filterForm = panel?.querySelector('.filter-form');
            const badge = toggle?.querySelector('[data-filter-badge]');

            const lockScroll = () => {
                const scrollY = window.scrollY || window.pageYOffset || 0;
                body.dataset.scrollY = String(scrollY);
                body.style.position = 'fixed';
                body.style.top = `-${scrollY}px`;
                body.style.left = '0';
                body.style.right = '0';
                body.style.width = '100%';
                html.style.overflow = 'hidden';
            };

            const unlockScroll = () => {
                const scrollY = Number(body.dataset.scrollY ?? '0') || 0;
                body.style.position = '';
                body.style.top = '';
                body.style.left = '';
                body.style.right = '';
                body.style.width = '';
                html.style.overflow = '';
                delete body.dataset.scrollY;
                window.scrollTo(0, scrollY);
            };

            const closeFilters = () => {
                panel?.classList.remove('is-open');
                backdrop?.classList.remove('is-visible');
                body.classList.remove('filters-open');
                toggle?.setAttribute('aria-expanded', 'false');
                unlockScroll();
            };

            const openFilters = () => {
                panel?.classList.add('is-open');
                backdrop?.classList.add('is-visible');
                body.classList.add('filters-open');
                toggle?.setAttribute('aria-expanded', 'true');
                lockScroll();
                updateBadge();
            };

            const getActiveCount = () => {
                if (!filterForm) return 0;
                const checked = Array.from(filterForm.querySelectorAll('input[type="checkbox"]:checked'));
                const unique = new Set(checked.map((input) => `${input.name}::${input.value}`));
                let count = unique.size;
                const q = filterForm.querySelector('input[name="q"]');
                if (q && q.value.trim() !== '') {
                    count += 1;
                }
                return count;
            };

            const updateBadge = () => {
                if (!badge) return;
                const count = getActiveCount();
                badge.textContent = String(count);
                badge.setAttribute('aria-label', `${count} filtre(s) actif(s)`);
                badge.style.display = count > 0 ? 'inline-flex' : 'none';
            };

            toggle?.addEventListener('click', (event) => {
                event.preventDefault();
                const isOpen = panel?.classList.contains('is-open');
                if (isOpen) {
                    closeFilters();
                } else {
                    openFilters();
                }
            });

            backdrop?.addEventListener('click', closeFilters);
            closes.forEach((btn) => btn.addEventListener('click', closeFilters));
            clearBtn?.addEventListener('click', () => {
                const form = panel?.querySelector('form');
                if (!form) return;
                const clearUrl = form.getAttribute('data-clear-url') || window.location.pathname;
                const sortValue = form.querySelector('input[name="sort"]')?.value || 'relevance';
                const target = sortValue ? `${clearUrl}?sort=${encodeURIComponent(sortValue)}` : clearUrl;
                window.location.assign(target);
            });
            filterForm?.addEventListener('change', updateBadge);
            filterForm?.querySelector('input[name="q"]')?.addEventListener('input', updateBadge);
            filterForm?.addEventListener('submit', () => {
                closeFilters();
            });
            updateBadge();
            window.addEventListener('keyup', (event) => {
                if (event.key === 'Escape') {
                    closeFilters();
                }
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024) {
                    closeFilters();
                }
            });
        });
    </script>
@endpush
@endsection
