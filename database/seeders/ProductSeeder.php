<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed sample products across the existing categories and brands.
     */
    public function run(): void
    {
        $categories = Category::all();
        $brands = Brand::query()->pluck('name');
        $brand = fn () => ['brand' => $brands->isNotEmpty() ? $brands->random() : fake()->company()];

        Product::factory()->count(30)->recycle($categories)->state($brand)->create();
        Product::factory()->count(8)->onSale()->recycle($categories)->state($brand)->create();
        Product::factory()->count(2)->outOfStock()->recycle($categories)->state($brand)->create();
    }
}
