<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product as CatalogProduct;
use App\Models\Legacy\Repair;
use App\Services\Catalog\ProductCatalogService;

class ProductController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalog,
    ) {
    }

    public function show(Repair $repair)
    {
        $catalogProduct = CatalogProduct::query()
            ->where('internal_reference', 'LEGACY-'.$repair->id)
            ->first();

        return view('front.catalog.product', [
            'product' => $repair,
            'catalogProduct' => $catalogProduct,
            'related' => $this->catalog->relatedProducts($repair),
        ]);
    }
}
