<div class="group bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-lg hover:border-blue-300 transition-all duration-300">
    <a href="{{ route('products.show', ['repair' => $product->id, 'slug' => $product->slug]) }}" class="block">
        <figure class="aspect-square overflow-hidden">
            <img src="{{ $product->image_fallback }}" alt="{{ $product->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        </figure>
        <div class="p-4">
            <p class="text-sm text-gray-500 mb-1">{{ $product->brand }} • {{ $product->model }}</p>
            <h3 class="font-semibold text-gray-800 truncate group-hover:text-blue-600 transition-colors">{{ $product->problem }}</h3>
            <div class="text-lg font-bold text-slate-800 mt-2">
                @if ($product->supplier_stock && $product->supplier_stock > 0)
                    {{ $product->display_price }} €
                    <span class="text-sm font-normal text-gray-500">TTC</span>
                @else
                    <span class="text-base font-medium text-red-500">Rupture de stock</span>
                @endif
            </div>
        </div>
    </a>
</div>