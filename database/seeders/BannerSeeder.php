<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Seed sample homepage banners.
     */
    public function run(): void
    {
        Banner::factory()->count(3)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
    }
}
