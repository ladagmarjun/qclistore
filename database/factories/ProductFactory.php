<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Colors a product can come in.
     *
     * @var list<string>
     */
    public const COLORS = ['Black', 'Brown', 'Tan', 'Cognac', 'Burgundy', 'Navy', 'Olive', 'Cream'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->randomElement(['Classic', 'Heritage', 'Urban', 'Vintage', 'Luxe', 'Everyday'])
            .' '.fake()->randomElement(['Tote', 'Satchel', 'Crossbody', 'Clutch', 'Backpack', 'Wallet', 'Hobo', 'Bucket Bag']));
        $slug = Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 99999);
        $colors = fake()->randomElements(self::COLORS, fake()->numberBetween(1, 4));
        $price = fake()->numberBetween(8, 150) * 100 - 1;

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->paragraphs(2, true),
            'price' => $price,
            'was_price' => null,
            'tag' => fake()->optional(0.4)->randomElement(['Bestseller', 'New']),
            'brand' => fake()->company(),
            'leather_type' => fake()->randomElement(['Full-grain', 'Top-grain', 'Genuine', 'Saffiano', 'Suede', 'Nappa']),
            'hardware' => fake()->randomElement(['Gold-tone', 'Silver-tone', 'Gunmetal', 'Brass']),
            'dimensions' => fake()->numberBetween(15, 45).' x '.fake()->numberBetween(10, 35).' x '.fake()->numberBetween(5, 18).' cm',
            'colors' => $colors,
            'glyph' => '👜',
            'image_url' => "https://picsum.photos/seed/{$slug}/800/800",
            'images' => array_map(fn (string $color) => [
                'url' => 'https://picsum.photos/seed/'.$slug.'-'.Str::slug($color).'/800/800',
                'color' => $color,
            ], $colors),
            'links' => [
                'shopee' => "https://shopee.ph/{$slug}",
                'lazada' => "https://www.lazada.com.ph/products/{$slug}.html",
                'tiktok' => "https://www.tiktok.com/view/product/{$slug}",
            ],
            'rating' => fake()->randomFloat(1, 3.5, 5),
            'review_count' => fake()->numberBetween(0, 500),
            'stock' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'tag' => 'Sale',
            'was_price' => round($attributes['price'] * fake()->randomFloat(2, 1.15, 1.5), -2) - 1,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Indicate that the product is hidden from the storefront.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
