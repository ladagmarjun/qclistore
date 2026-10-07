<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement' => 'hero',
            'image_url' => 'https://picsum.photos/seed/banner-'.fake()->unique()->numberBetween(1, 99999).'/1600/600',
            'headline' => fake()->sentence(4),
            'subtext' => fake()->sentence(),
            'link_url' => '/products',
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the banner belongs to the mid-page slideshow.
     */
    public function middle(): static
    {
        return $this->state(fn (array $attributes) => [
            'placement' => 'middle',
        ]);
    }

    /**
     * Indicate that the banner is hidden from the storefront.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
