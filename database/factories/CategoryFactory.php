<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Make it a subcategory of the given top-level category.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
