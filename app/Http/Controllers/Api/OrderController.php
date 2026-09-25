<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * List the signed-in customer's orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            $request->user()->orders()->withCount('items')->latest()->paginate(10),
        );
    }

    /**
     * Show one of the signed-in customer's orders.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return OrderResource::make($order->load('items.product'));
    }

    /**
     * Place an order. Guests can check out; a bearer token links the order to the account.
     */
    public function store(PlaceOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->place(
            $request->customerDetails(),
            $request->orderItems(),
            $request->user('sanctum'),
        );

        return OrderResource::make($order)->response()->setStatusCode(201);
    }
}
