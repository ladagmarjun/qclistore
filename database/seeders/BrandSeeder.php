<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Seed sample brands.
     */
    public function run(): void
    {
        Brand::factory()->count(6)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
    }
}
