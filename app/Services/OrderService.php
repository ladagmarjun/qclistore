<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * The statuses an order may move to from each status.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /**
     * Place an order, pricing it from the database and reserving stock.
     *
     * @param  array{customer_name: string, customer_email: string, customer_phone?: string|null, shipping_address: string, city?: string|null, province?: string|null, postal_code?: string|null, payment_method: string, notes?: string|null}  $customer
     * @param  list<array{product_id: int, quantity: int, color?: string|null}>  $items
     *
     * @throws ValidationException
     */
    public function place(array $customer, array $items, ?User $user = null): Order
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Your cart is empty.']);
        }

        return DB::transaction(function () use ($customer, $items, $user) {
            $products = Product::query()
                ->whereKey(array_column($items, 'product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalCents = 0;
            $lines = [];

            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}" => 'This product is no longer available.',
                    ]);
                }

                if ($item['quantity'] < 1) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Quantity must be at least 1.',
                    ]);
                }

                if ($product->colors && ! in_array($item['color'] ?? null, $product->colors, true)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.color" => "Choose one of these colors for {$product->name}: ".implode(', ', $product->colors).'.',
                    ]);
                }

                // The same product can appear on several lines, so check against what earlier lines already took.
                $color = $product->colors ? ($item['color'] ?? null) : null;
                $available = $product->stockFor($color);

                if ($available < $item['quantity']) {
                    $what = $color ? "{$product->name} in {$color}" : $product->name;

                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => $available > 0 ? "Only {$available} of {$what} left in stock." : "{$what} is out of stock.",
                    ]);
                }

                $product->adjustStock($color, $item['quantity']);
                $product->save();

                $totalCents += (int) round((float) $product->price * 100) * $item['quantity'];
                $lines[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'color' => $item['color'] ?? null,
                ];
            }

            $order = Order::query()->create([
                ...$customer,
                'user_id' => $user?->id,
                'total_amount' => number_format($totalCents / 100, 2, '.', ''),
                'status' => 'pending',
            ]);

            $order->items()->createMany($lines);

            return $order->load('items.product');
        });
    }

    /**
     * Move an order to a new status, returning stock if it is cancelled.
     *
     * @throws ValidationException
     */
    public function updateStatus(Order $order, string $status): Order
    {
        if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "An order that is {$order->status} can't be marked as {$status}.",
            ]);
        }

        return DB::transaction(function () use ($order, $status) {
            if ($status === 'cancelled') {
                $this->restock($order);
            }

            $order->update(['status' => $status]);

            return $order;
        });
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Order $order): Order
    {
        return $this->updateStatus($order, 'cancelled');
    }

    private function restock(Order $order): void
    {
        $products = Product::query()
            ->whereKey($order->items->pluck('product_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($order->items as $item) {
            $product = $products->get($item->product_id);
            $product?->adjustStock($item->color, -$item->quantity);
        }

        $products->each->save();
    }
}
