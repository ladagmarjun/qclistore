<?php

namespace App\Services;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardService
{
    /**
     * Store stats, the latest orders, and products running low.
     *
     * @return array{stats: array<string, int|string>, recent_orders: array<int, mixed>, low_stock_products: array<int, mixed>}
     */
    public function summary(): array
    {
        $lowStock = Product::query()->where('is_active', true)->where('stock', '<', 5);

        return [
            'stats' => [
                'revenue' => number_format((float) Order::query()->where('status', '!=', 'cancelled')->sum('total_amount'), 2, '.', ''),
                'pending_orders' => Order::query()->where('status', 'pending')->count(),
                'active_products' => Product::query()->where('is_active', true)->count(),
                'low_stock' => (clone $lowStock)->count(),
                'customers' => User::role('customer')->count(),
            ],
            'recent_orders' => OrderResource::collection(Order::query()->latest()->limit(8)->get())->resolve(),
            'low_stock_products' => ProductResource::collection($lowStock->orderBy('stock')->limit(8)->get())->resolve(),
        ];
    }
}
