<?php

namespace App\Services\Commerce;

use App\Enums\InventoryMovementType;
use App\Models\Catalog\InventoryMovement;
use App\Models\Commerce\Order;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function decrementForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $variant = $item->variant;
                if (! $variant) {
                    continue;
                }

                $variant->decrement('stock_on_hand', $item->quantity);
                InventoryMovement::create([
                    'product_variant_id' => $variant->id,
                    'type' => InventoryMovementType::Out,
                    'quantity' => $item->quantity,
                    'source' => 'order:'.$order->number,
                ]);
            }
        });
    }

    public function restockFromRma(int $variantId, int $quantity, ?string $notes = null): void
    {
        InventoryMovement::create([
            'product_variant_id' => $variantId,
            'type' => InventoryMovementType::Return,
            'quantity' => $quantity,
            'notes' => $notes,
        ]);
    }
}
