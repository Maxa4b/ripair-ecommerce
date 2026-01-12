@extends('layouts.app')

@section('content')
    <div class="catalog-search">
        <form>
            <input type="search" name="q" value="{{ $term }}" placeholder="Écran iPhone 13, batterie Switch, etc.">
            <button class="btn" style="margin-top:1rem;">Rechercher</button>
        </form>
    </div>

    @if ($term)
        <h2 class="section-title" style="text-align:left;">Résultats pour « {{ $term }} »</h2>
        <div class="product-card-list">
            @forelse ($results as $result)
                <article class="catalog-card">
                    <div>
                        <small>{{ $result->category }} • {{ $result->brand }}</small>
                        <h3><a href="{{ route('products.show', ['repair' => $result->id, 'slug' => $result->slug]) }}">{{ $result->problem }}</a></h3>
                        <p>{{ $result->model }}</p>
                    </div>
                    <div class="price">
                        @if ($result->supplier_stock && $result->supplier_stock > 0)
                            {{ $result->display_price }} € TTC
                        @else
                            Rupture de stock
                        @endif
                        <span>{{ $result->duration ?? 'Atelier' }}</span>
                    </div>
                </article>
            @empty
                <p>Aucun résultat n'a été trouvé.</p>
            @endforelse
        </div>
    @endif
@endsection
