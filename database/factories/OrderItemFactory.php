<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => fn (array $attributes) => Product::query()->whereKey($attributes['product_id'])->firstOrFail()->price,
            'color' => function (array $attributes) {
                $colors = Product::query()->whereKey($attributes['product_id'])->firstOrFail()->colors;

                return $colors ? fake()->randomElement($colors) : null;
            },
        ];
    }
}
