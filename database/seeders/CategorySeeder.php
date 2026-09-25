<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed the store's product categories.
     */
    public function run(): void
    {
        $categories = ['Handbags', 'Totes', 'Crossbody Bags', 'Backpacks', 'Wallets', 'Clutches'];

        foreach ($categories as $sortOrder => $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $sortOrder],
            );
        }
    }
}
