<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Catalog\ProductCatalogService;
use App\Services\Catalog\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogSearchController extends Controller
{
    public function __construct(
        protected SearchService $search,
        protected ProductCatalogService $catalog,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $term = $request->string('q', '')->toString();

        return response()->json([
            'results' => $this->search->search($term, $request->all()),
        ]);
    }

    public function filters(): JsonResponse
    {
        return response()->json($this->catalog->listFilters());
    }
}
