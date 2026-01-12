<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderStatusRequest;
use App\Models\Commerce\Order;
use Illuminate\Http\JsonResponse;

class OrderStatusController extends Controller
{
    public function __invoke(Order $order, OrderStatusRequest $request): JsonResponse
    {
        $from = $order->status?->value;
        $order->update(['status' => $request->input('status')]);
        $order->statusHistory()->create([
            'from_status' => $from,
            'to_status' => $request->input('status'),
            'user_id' => $request->user()?->id,
            'comment' => $request->input('comment'),
        ]);

        return response()->json(['order' => $order->refresh()]);
    }
}
