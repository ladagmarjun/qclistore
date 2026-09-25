<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-90 days');

        return [
            'user_id' => User::factory(),
            'customer_name' => fn (array $attributes) => $attributes['user_id']
                ? User::query()->whereKey($attributes['user_id'])->firstOrFail()->name
                : fake()->name(),
            'customer_email' => fn (array $attributes) => $attributes['user_id']
                ? User::query()->whereKey($attributes['user_id'])->firstOrFail()->email
                : fake()->safeEmail(),
            'customer_phone' => '09'.fake()->numerify('#########'),
            'shipping_address' => fake()->buildingNumber().' '.fake()->streetName(),
            'city' => fake()->randomElement(['Quezon City', 'Makati', 'Pasig', 'Taguig', 'Cebu City', 'Davao City', 'Iloilo City']),
            'province' => fake()->randomElement(['Metro Manila', 'Cebu', 'Davao del Sur', 'Iloilo', 'Laguna', 'Cavite']),
            'postal_code' => fake()->numerify('####'),
            'total_amount' => 0,
            'status' => fake()->randomElement(Order::STATUSES),
            'payment_method' => fake()->randomElement(Order::PAYMENT_METHODS),
            'notes' => fake()->optional(0.2)->sentence(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Indicate that the order was placed without an account.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Set the order's status.
     */
    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Add line items from existing products and set the order total to match.
     */
    public function withItems(int $min = 1, int $max = 4): static
    {
        return $this->afterCreating(function (Order $order) use ($min, $max) {
            $count = fake()->numberBetween($min, $max);
            $products = Product::query()->inRandomOrder()->limit($count)->get();

            if ($products->isEmpty()) {
                $products = Product::factory()->count($count)->create();
            }

            foreach ($products as $product) {
                OrderItem::factory()->for($order)->for($product)->create([
                    'created_at' => $order->created_at,
                ]);
            }

            $order->update([
                'total_amount' => $order->items()->sum(DB::raw('quantity * unit_price')),
            ]);
        });
    }
}
