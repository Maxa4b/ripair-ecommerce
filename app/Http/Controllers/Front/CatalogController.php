<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Catalog\ProductCatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalog,
    ) {
    }

    public function index(Request $request)
    {
        return view('front.catalog.index', [
            'category' => null,
            'products' => $this->catalog->filterProducts($request->all()),
            'filters' => $this->catalog->listFilters(),
            'activeFilters' => $request->all(),
        ]);
    }

    public function show(Request $request, string $category)
    {
        return view('front.catalog.index', [
            'category' => $category,
            'products' => $this->catalog->filterProducts($request->all(), $category),
            'filters' => $this->catalog->listFilters($category),
            'activeFilters' => $request->all(),
        ]);
    }

    public function filter(Request $request, string $category, ?string $model = null)
    {
        return view('front.catalog.index', [
            'category' => $category,
            'model' => $model,
            'products' => $this->catalog->filterProducts($request->all(), $category, $model),
            'filters' => $this->catalog->listFilters($category),
            'activeFilters' => array_merge($request->all(), ['model' => $model ? [$model] : null]),
        ]);
    }
}
