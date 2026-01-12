<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use Illuminate\Http\JsonResponse;

class ProductAvailabilityController extends Controller
{
    public function __invoke(Product $product): JsonResponse
    {
        $product->load('variants');

        return response()->json([
            'product' => $product->only(['id', 'name', 'internal_reference']),
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'stock' => $variant->available_stock,
                'availability' => $variant->availability_status->value,
                'price_ttc' => $variant->price_ttc,
            ]),
        ]);
    }
}
