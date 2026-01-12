<?php

namespace App\Jobs;

use App\Models\Catalog\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RHCatalogoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $productId;

    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    public function handle(): void
    {
        $product = Product::query()->find($this->productId);
        if (!$product) {
            return;
        }

        $supplierRef = (string) ($product->supplier_reference ?? '');
        if ($supplierRef === '') {
            return;
        }

        RHCatalogoSupplierJob::dispatch($supplierRef);
    }
}
