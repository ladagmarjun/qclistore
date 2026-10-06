<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Storage;

test('seeded categories get a placeholder image', function () {
    Storage::fake('public');

    $this->seed(CategorySeeder::class);

    $totes = Category::query()->where('slug', 'totes')->sole();

    expect($totes->image_url)->toEndWith('storage/categories/totes.svg');
    Storage::disk('public')->assertExists('categories/totes.svg');
});

test('reseeding keeps an image an admin already set', function () {
    Storage::fake('public');
    Category::factory()->create(['name' => 'Totes', 'slug' => 'totes', 'image_url' => 'https://example.com/totes.jpg']);

    $this->seed(CategorySeeder::class);

    expect(Category::query()->where('slug', 'totes')->sole()->image_url)->toBe('https://example.com/totes.jpg');
    Storage::disk('public')->assertMissing('categories/totes.svg');
});
