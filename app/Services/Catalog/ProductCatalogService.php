<?php

namespace App\Services\Catalog;

use App\Models\Legacy\Repair;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductCatalogService
{
    public function featuredFamilies(): \Illuminate\Support\Collection
    {
        return Repair::query()
            ->select('category')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(fn ($record) => (object) [
                'name' => $record->category,
                'value' => $record->category,
                'slug' => Str::slug($record->category),
            ]);
    }

    public function listFilters(?string $category = null): array
    {
        $baseQuery = Repair::query();

        if ($category) {
            $baseQuery->where('category', $category);
        }

        $categories = Repair::query()
            ->select('category')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('category');

        $brands = (clone $baseQuery)
            ->select('brand')
            ->whereNotNull('brand')
            ->groupBy('brand')
            ->orderBy('brand')
            ->pluck('brand');

        $models = (clone $baseQuery)
            ->select('model')
            ->whereNotNull('model')
            ->groupBy('model')
            ->orderBy('model')
            ->pluck('model');

        $problems = (clone $baseQuery)
            ->select('problem')
            ->whereNotNull('problem')
            ->groupBy('problem')
            ->orderBy('problem')
            ->pluck('problem');

        return [
            'brands' => $brands,
            'models' => $models,
            'problems' => $problems,
            'categories' => $categories,
        ];
    }

    public function filterProducts(array $filters = [], ?string $category = null, ?string $forcedModel = null): LengthAwarePaginator
    {
        $query = Repair::query();

        $selectedCategories = Arr::get($filters, 'category');

        if ($selectedCategories) {
            $query->whereIn('category', Arr::wrap($selectedCategories));
        } elseif ($category) {
            $query->where('category', $category);
        }

        if ($model = $forcedModel ?? Arr::get($filters, 'model')) {
            $query->whereIn('model', Arr::wrap($model));
        }

        if ($brand = Arr::get($filters, 'brand')) {
            $query->whereIn('brand', Arr::wrap($brand));
        }

        if ($problem = Arr::get($filters, 'problem')) {
            $query->whereIn('problem', Arr::wrap($problem));
        }

        if ($term = Arr::get($filters, 'q')) {
            $query->search($term);
        }

        $sort = Arr::get($filters, 'sort', 'latest');

        // Pour les tris prix, on trie sur le computed_price (aligné avec l'affichage)
        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $items = $query->get();
            $sorted = $sort === 'price_asc'
                ? $items->sortBy(fn ($item) => $item->computed_price)
                : $items->sortByDesc(fn ($item) => $item->computed_price);

            $perPage = 24;
            $page = LengthAwarePaginator::resolveCurrentPage();
            $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

            return new LengthAwarePaginator(
                $slice,
                $sorted->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $this->applySort($query, $sort);

        return $query->paginate(24)->withQueryString();
    }

    public function relatedProducts(Repair $repair, int $limit = 6): Collection
    {
        return Repair::query()
            ->where('id', '!=', $repair->id)
            ->where(function ($builder) use ($repair): void {
                $builder
                    ->where('category', $repair->category)
                    ->orWhere('brand', $repair->brand)
                    ->orWhere('model', $repair->model);
            })
            ->limit($limit)
            ->get();
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
