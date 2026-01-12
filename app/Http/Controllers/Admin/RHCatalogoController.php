<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RHCatalogoSupplierJob;
use App\Models\Catalog\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RHCatalogoController extends Controller
{
    private function frontUrlForInternalReference(?string $internalReference): ?string
    {
        if (!$internalReference || !Str::startsWith($internalReference, 'LEGACY-')) {
            return null;
        }

        $id = (int) Str::after($internalReference, 'LEGACY-');
        if ($id <= 0) {
            return null;
        }

        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            $base = 'https://boutique.ripair.shop';
        }

        return "{$base}/produits/{$id}";
    }

    public function stats(): JsonResponse
    {
        $total = Product::count();
        $pending = Product::whereNull('rhc_status')
            ->orWhereIn('rhc_status', ['pending', 'processing', 'error'])
            ->count();
        $ok = Product::where('rhc_status', 'ok')->count();
        $errors = Product::where('rhc_status', 'error')->count();

        return response()->json([
            'total' => $total,
            'pending' => $pending,
            'ok' => $ok,
            'errors' => $errors,
        ]);
    }

    public function enqueue(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 100);
        $onlyPending = filter_var($request->boolean('only_pending', true), FILTER_VALIDATE_BOOLEAN);

        $count = 0;

        $supplierRefs = Product::query()
            ->join('repairs as r', 'r.supplier_ref', '=', 'products.supplier_reference')
            ->whereNotNull('supplier_reference')
            ->where(function ($q) {
                $q->whereNull('r.update_done')->orWhere('r.update_done', false);
            })
            ->when($onlyPending, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('rhc_status')->orWhereIn('rhc_status', ['pending', 'error']);
                });
            })
            ->selectRaw('supplier_reference,
                MIN(CASE WHEN rhc_status = \'error\' THEN 0 ELSE 1 END) AS has_error,
                MAX(is_best_seller) AS best_seller,
                MAX(is_published) AS published')
            ->groupBy('supplier_reference')
            ->orderBy('has_error')
            ->orderByDesc('best_seller')
            ->orderByDesc('published')
            ->limit($limit)
            ->pluck('supplier_reference');

        foreach ($supplierRefs as $supplierRef) {
            RHCatalogoSupplierJob::dispatch((string) $supplierRef);
            $count++;
        }

        return response()->json([
            'enqueued' => $count,
        ]);
    }

    public function retry(Request $request, Product $product): JsonResponse
    {
        $supplierRef = (string) ($product->supplier_reference ?? '');
        if ($supplierRef === '') {
            return response()->json(['error' => 'supplier_reference manquant'], 422);
        }

        Product::query()
            ->where('supplier_reference', $supplierRef)
            ->update([
                'rhc_status' => 'pending',
                'rhc_last_error' => null,
            ]);

        RHCatalogoSupplierJob::dispatch($supplierRef);

        return response()->json(['status' => 'queued']);
    }

    public function items(Request $request): JsonResponse
    {
        $status = $request->get('status');
        $search = trim((string) $request->get('q', ''));
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 200);

        $query = Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'rhc_status',
                'rhc_attempts',
                'rhc_last_run_at',
                'rhc_generated_title',
                'rhc_generated_description',
                'rhc_generated_image',
                'rhc_last_error',
                'supplier_reference',
                'internal_reference',
                'brand_id',
                'category_id',
            ])
            ->with(['brand:id,name', 'category:id,name']);

        if ($status) {
            if ($status === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('rhc_status')->orWhereIn('rhc_status', ['pending', 'processing', 'error']);
                });
            } else {
                $query->where('rhc_status', $status);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('rhc_generated_title', 'like', "%{$search}%")
                    ->orWhere('supplier_reference', 'like', "%{$search}%")
                    ->orWhere('internal_reference', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw("
            CASE
                WHEN rhc_status = 'error' THEN 0
                WHEN rhc_status = 'processing' THEN 1
                WHEN rhc_status IS NULL THEN 2
                WHEN rhc_status = 'pending' THEN 3
                WHEN rhc_status = 'ok' THEN 4
                ELSE 5
            END
        ")->latest('rhc_last_run_at');

        $items = $query->paginate($perPage);
        $items->getCollection()->transform(function (Product $product) {
            $product->front_url = $this->frontUrlForInternalReference($product->internal_reference);
            return $product;
        });

        return response()->json($items);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['brand:id,name', 'category:id,name']);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'rhc_status' => $product->rhc_status,
            'rhc_attempts' => $product->rhc_attempts,
            'rhc_last_run_at' => $product->rhc_last_run_at,
            'rhc_generated_title' => $product->rhc_generated_title,
            'rhc_generated_description' => $product->rhc_generated_description,
            'rhc_generated_image' => $product->rhc_generated_image,
            'rhc_last_error' => $product->rhc_last_error,
            'rhc_last_payload' => $product->rhc_last_payload,
            'supplier_reference' => $product->supplier_reference,
            'internal_reference' => $product->internal_reference,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'front_url' => $this->frontUrlForInternalReference($product->internal_reference),
        ]);
    }

    public function events(): JsonResponse
    {
        $events = Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'internal_reference',
                'rhc_status',
                'rhc_last_run_at',
                'rhc_generated_title',
                'rhc_generated_description',
                'rhc_generated_image',
                'rhc_last_error',
                'rhc_attempts',
            ])
            ->whereNotNull('rhc_last_run_at')
            ->orderByDesc('rhc_last_run_at')
            ->limit(50)
            ->get();

        $events->each(function (Product $product) {
            $product->front_url = $this->frontUrlForInternalReference($product->internal_reference);
        });

        return response()->json($events);
    }
}
