<?php

namespace App\Services\Catalog;

use App\Models\Legacy\Repair;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    public function search(string $term): Collection
    {
        return Repair::query()
            ->search($term)
            ->orderByDesc('created_at')
            ->limit(60)
            ->get();
    }

    public function suggestions(string $term): Collection
    {
        return Cache::remember("search:suggestions:{$term}", now()->addMinutes(15), function () use ($term): Collection {
            return Repair::query()
                ->select(['id', 'brand', 'model', 'problem'])
                ->search($term)
                ->limit(10)
                ->get();
        });
    }
}
