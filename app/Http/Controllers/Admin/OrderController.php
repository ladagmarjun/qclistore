<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * List orders. Filters: status, search (name, email or order number).
     */
    public function index(Request $request): Response
    {
        $filters = array_filter($request->only(['status', 'search']));

        return Inertia::render('admin/orders/index', [
            'orders' => OrderResource::collection(
                Order::query()->filter($filters)->withCount('items')->latest()->paginate(20)->withQueryString(),
            ),
            'filters' => $filters,
            'statuses' => Order::STATUSES,
        ]);
    }

    /**
     * Show an order, plus the statuses it can move to next.
     */
    public function show(Order $order): Response
    {
        return Inertia::render('admin/orders/show', [
            'order' => OrderResource::make($order->load(['items.product', 'user']))->resolve(),
            'nextStatuses' => OrderService::TRANSITIONS[$order->status] ?? [],
        ]);
    }

    /**
     * Move the order to a new status. Cancelling returns its stock.
     */
    public function update(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $orders->updateStatus($order, $validated['status']);

        Inertia::flash('success', __('Order marked as :status.', ['status' => Str::headline($validated['status'])]));

        return to_route('admin.orders.show', $order);
    }
}
