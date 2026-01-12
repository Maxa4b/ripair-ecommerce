<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Catalog\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $search,
    ) {
    }

    public function index(Request $request)
    {
        $term = $request->string('q')->toString();

        return view('front.search.index', [
            'term' => $term,
            'results' => $term ? $this->search->search($term) : collect(),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->string('q')->toString();

        if (! $term) {
            return response()->json([]);
        }

        return response()->json(
            $this->search->suggestions($term)->map(fn ($item) => [
                'id' => $item->id,
                'title' => "{$item->brand} {$item->model} – {$item->problem}",
                'url' => route('products.show', ['repair' => $item->id, 'slug' => $item->slug]),
            ])
        );
    }
}
