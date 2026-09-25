<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * List orders. Filters: status, search (name, email or order number).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->filter($request->only(['status', 'search']))
            ->withCount('items')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    /**
     * Show an order, plus the statuses it can move to next.
     */
    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load(['items.product', 'user']))->additional([
            'next_statuses' => OrderService::TRANSITIONS[$order->status] ?? [],
        ]);
    }

    /**
     * Move the order to a new status. Cancelling returns its stock.
     */
    public function update(Request $request, Order $order, OrderService $orders): OrderResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $orders->updateStatus($order, $validated['status']);

        return OrderResource::make($order->load(['items.product', 'user']))->additional([
            'next_statuses' => OrderService::TRANSITIONS[$order->status] ?? [],
        ]);
    }
}
