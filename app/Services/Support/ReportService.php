<?php

namespace App\Services\Support;

use App\Models\Commerce\Order;
use App\Models\Commerce\OrderItem;
use App\Models\Sav\RmaRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function dashboardMetrics(): array
    {
        $ordersQuery = Order::query()->where('status', '!=', 'cancelled');

        return [
            'revenue' => $ordersQuery->sum('total_ttc'),
            'orders' => $ordersQuery->count(),
            'average_cart' => $ordersQuery->avg('total_ttc'),
            'return_rate' => $this->returnRate(),
        ];
    }

    public function revenueByCategory(): Collection
    {
        return OrderItem::query()
            ->selectRaw('categories.name as category, sum(order_items.total_ttc) as total')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
    }

    public function exportOrders(): Collection
    {
        return Order::query()
            ->select(['number', 'total_ttc', 'status', 'placed_at'])
            ->orderByDesc('placed_at')
            ->limit(500)
            ->get();
    }

    private function returnRate(): float
    {
        $totalOrders = max(1, Order::query()->count());
        $rmaCount = RmaRequest::query()->count();

        return round(($rmaCount / $totalOrders) * 100, 2);
    }
}
