<?php

use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;

function customerDetails(): array
{
    return [
        'customer_name' => 'Juan Dela Cruz',
        'customer_email' => 'juan@example.com',
        'shipping_address' => '123 Rizal St',
        'city' => 'Makati',
        'payment_method' => 'cod',
    ];
}

test('placing an order prices items from the database and reserves stock', function () {
    $user = User::factory()->create();
    $bag = Product::factory()->create(['colors' => [], 'price' => 1499.50, 'stock' => 5]);
    $wallet = Product::factory()->create(['colors' => [], 'price' => 799, 'stock' => 3]);

    $order = app(OrderService::class)->place(customerDetails(), [
        ['product_id' => $bag->id, 'quantity' => 2, 'color' => 'Black'],
        ['product_id' => $wallet->id, 'quantity' => 1],
    ], $user);

    expect($order->total_amount)->toBe('3798.00')
        ->and($order->status)->toBe('pending')
        ->and($order->user_id)->toBe($user->id)
        ->and($order->items)->toHaveCount(2)
        ->and($bag->fresh()->stock)->toBe(3)
        ->and($wallet->fresh()->stock)->toBe(2);
});

test('a product with colors needs one of its colors', function (?string $color) {
    $product = Product::factory()->create(['colors' => ['Black', 'Tan'], 'stock' => 5]);

    app(OrderService::class)->place(customerDetails(), [
        ['product_id' => $product->id, 'quantity' => 1, 'color' => $color],
    ]);
})->with([null, 'Purple'])->throws(ValidationException::class);

test('an order cannot take more than the available stock', function () {
    $product = Product::factory()->create(['colors' => [], 'stock' => 1]);

    app(OrderService::class)->place(customerDetails(), [
        ['product_id' => $product->id, 'quantity' => 2],
    ]);
})->throws(ValidationException::class);

test('a failed order leaves stock untouched', function () {
    $inStock = Product::factory()->create(['colors' => [], 'stock' => 5]);
    $soldOut = Product::factory()->outOfStock()->create();

    try {
        app(OrderService::class)->place(customerDetails(), [
            ['product_id' => $inStock->id, 'quantity' => 1],
            ['product_id' => $soldOut->id, 'quantity' => 1],
        ]);
    } catch (ValidationException) {
    }

    expect($inStock->fresh()->stock)->toBe(5);
});

test('inactive products cannot be ordered', function () {
    $product = Product::factory()->inactive()->create();

    app(OrderService::class)->place(customerDetails(), [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);
})->throws(ValidationException::class);

test('cancelling an order returns its stock', function () {
    $product = Product::factory()->create(['colors' => [], 'stock' => 4]);
    $service = app(OrderService::class);

    $order = $service->place(customerDetails(), [['product_id' => $product->id, 'quantity' => 3]]);
    $service->cancel($order);

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(4);
});

test('orders cannot skip or reverse statuses', function () {
    $product = Product::factory()->create(['colors' => [], 'stock' => 4]);
    $service = app(OrderService::class);

    $order = $service->place(customerDetails(), [['product_id' => $product->id, 'quantity' => 1]]);

    $service->updateStatus($order, 'delivered');
})->throws(ValidationException::class);

test('each colour has its own stock', function () {
    $product = Product::factory()->create(['colors' => ['Black', 'Tan'], 'color_stock' => ['Black' => 3, 'Tan' => 1]]);
    $service = app(OrderService::class);

    $service->place(customerDetails(), [['product_id' => $product->id, 'quantity' => 2, 'color' => 'Black']]);

    expect($product->fresh()->color_stock)->toBe(['Black' => 1, 'Tan' => 1])
        ->and($product->fresh()->stock)->toBe(2);

    expect(fn () => $service->place(customerDetails(), [['product_id' => $product->id, 'quantity' => 2, 'color' => 'Tan']]))
        ->toThrow(ValidationException::class, 'Only 1 of');
});

test('a sold-out colour cannot be ordered even when other colours are in stock', function () {
    $product = Product::factory()->create(['colors' => ['Black', 'Tan'], 'color_stock' => ['Black' => 5, 'Tan' => 0]]);

    expect(fn () => app(OrderService::class)->place(customerDetails(), [['product_id' => $product->id, 'quantity' => 1, 'color' => 'Tan']]))
        ->toThrow(ValidationException::class, 'is out of stock');
});

test('several lines of the same colour share its stock', function () {
    $product = Product::factory()->create(['colors' => ['Black'], 'color_stock' => ['Black' => 3]]);

    expect(fn () => app(OrderService::class)->place(customerDetails(), [
        ['product_id' => $product->id, 'quantity' => 2, 'color' => 'Black'],
        ['product_id' => $product->id, 'quantity' => 2, 'color' => 'Black'],
    ]))->toThrow(ValidationException::class);

    expect($product->fresh()->color_stock)->toBe(['Black' => 3]);
});

test('cancelling returns stock to the colour that was ordered', function () {
    $product = Product::factory()->create(['colors' => ['Black', 'Tan'], 'color_stock' => ['Black' => 2, 'Tan' => 2]]);
    $service = app(OrderService::class);

    $order = $service->place(customerDetails(), [['product_id' => $product->id, 'quantity' => 2, 'color' => 'Tan']]);
    $service->cancel($order->fresh());

    expect($product->fresh()->color_stock)->toBe(['Black' => 2, 'Tan' => 2])
        ->and($product->fresh()->stock)->toBe(4);
});

test('a total stock without per-colour numbers is split across the colours', function () {
    $product = Product::factory()->create(['colors' => ['Black', 'Tan', 'Navy'], 'stock' => 10]);

    expect($product->color_stock)->toBe(['Black' => 4, 'Tan' => 3, 'Navy' => 3])
        ->and($product->stock)->toBe(10);
});
