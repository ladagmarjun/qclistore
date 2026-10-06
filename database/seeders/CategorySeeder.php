<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed the store's product categories.
     */
    public function run(): void
    {
        // Name => tile colour for the placeholder image.
        $categories = [
            'Handbags' => '#7a4b2a',
            'Totes' => '#a0703c',
            'Crossbody Bags' => '#5c3a21',
            'Backpacks' => '#3f4a3c',
            'Wallets' => '#2f2a26',
            'Clutches' => '#8a2f3b',
        ];

        foreach (array_keys($categories) as $sortOrder => $name) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $sortOrder],
            );

            // Never replace an image an admin has chosen, but refresh our own placeholder so its URL follows APP_URL.
            if ($category->image_url === null || str_ends_with($category->image_url, "storage/categories/{$category->slug}.svg")) {
                $category->update(['image_url' => $this->placeholderImage($category, $categories[$name])]);
            }
        }
    }

    /**
     * Write a simple square tile with the category's name to the public disk and return its URL.
     */
    private function placeholderImage(Category $category, string $color): string
    {
        $path = "categories/{$category->slug}.svg";
        $name = e($category->name);

        Storage::disk('public')->put($path, <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
              <rect width="600" height="600" fill="{$color}"/>
              <rect x="30" y="30" width="540" height="540" fill="none" stroke="#ffffff" stroke-opacity="0.35" stroke-width="2"/>
              <text x="300" y="315" text-anchor="middle" font-family="Georgia, serif" font-size="48" fill="#ffffff">{$name}</text>
            </svg>
            SVG);

        return asset('storage/'.$path);
    }
}
