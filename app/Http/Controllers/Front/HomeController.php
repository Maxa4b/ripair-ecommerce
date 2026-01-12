<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Legacy\Repair;
use App\Services\Catalog\ProductCatalogService;

class HomeController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalog,
    ) {
    }

    public function index()
    {
        return view('front.home', [
            'families' => $this->catalog->featuredFamilies(),
            'newProducts' => Repair::query()->latest('created_at')->limit(8)->get(),
            'bestSellers' => Repair::query()->orderByDesc('supplier_stock')->limit(8)->get(),
        ]);
    }
}
