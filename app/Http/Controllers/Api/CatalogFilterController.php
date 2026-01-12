<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Legacy\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogFilterController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $query = Repair::query();

        if ($brands = $request->input('brands')) {
            $query->whereIn('brand', (array) $brands);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($categories = $request->input('categories')) {
            $query->whereIn('category', (array) $categories);
        }

        $models = (clone $query)
            ->select('model')
            ->whereNotNull('model')
            ->groupBy('model')
            ->orderBy('model')
            ->pluck('model');

        $problems = (clone $query)
            ->select('problem')
            ->whereNotNull('problem')
            ->groupBy('problem')
            ->orderBy('problem')
            ->pluck('problem');

        $categoriesList = (clone $query)
            ->select('category')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'models' => $models,
            'problems' => $problems,
            'categories' => $categoriesList,
        ]);
    }
}
