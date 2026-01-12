<?php

namespace App\Http\Controllers\Api;

use App\Enums\RmaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RmaUpdateRequest;
use App\Http\Requests\Front\StoreRmaRequest;
use App\Models\Commerce\Order;
use App\Models\Commerce\OrderItem;
use App\Models\Sav\RmaRequest;
use App\Services\Sav\RmaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RmaController extends Controller
{
    public function __construct(
        protected RmaService $service,
    ) {
    }

    public function store(StoreRmaRequest $request): JsonResponse
    {
        $order = Order::where('number', $request->string('order_number')->toString())
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $item = OrderItem::where('order_id', $order->id)->findOrFail($request->integer('order_item_id'));
        $rma = $this->service->create($order, $item, $request->validated());

        return response()->json($rma->load('items'), 201);
    }

    public function updateStatus(RmaUpdateRequest $request, RmaRequest $rmaRequest): JsonResponse
    {
        $this->service->updateStatus($rmaRequest, RmaStatus::from($request->string('status')->toString()), $request->input('comment'));

        return response()->json($rmaRequest->refresh());
    }

    public function export(): JsonResponse
    {
        $items = RmaRequest::query()->with('order')->latest()->limit(500)->get();

        return response()->json($items);
    }
}
