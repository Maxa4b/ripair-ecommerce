<?php

namespace App\Services\Sav;

use App\Enums\RmaStatus;
use App\Models\Commerce\Order;
use App\Models\Commerce\OrderItem;
use App\Models\Sav\RmaRequest;
use Illuminate\Support\Str;

class RmaService
{
    public function create(Order $order, OrderItem $item, array $payload): RmaRequest
    {
        return RmaRequest::create([
            'rma_number' => $this->nextRmaNumber(),
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'status' => RmaStatus::Received,
            'reason' => $payload['reason'],
            'description' => $payload['description'] ?? null,
            'conditions_confirmed' => !empty($payload['conditions']),
            'metadata' => [
                'photos' => $payload['photos'] ?? [],
            ],
        ]);
    }

    public function updateStatus(RmaRequest $request, RmaStatus $status, ?string $comment = null): RmaRequest
    {
        $request->update([
            'status' => $status,
        ]);

        if ($comment) {
            $request->comments()->create([
                'comment' => $comment,
                'is_internal' => true,
            ]);
        }

        return $request->refresh();
    }

    private function nextRmaNumber(): string
    {
        return 'RMA-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
    }
}
