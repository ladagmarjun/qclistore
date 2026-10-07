<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Seed sample banners for the hero and mid-page slideshows.
     */
    public function run(): void
    {
        Banner::factory()->count(3)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
        Banner::factory()->middle()->count(2)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
    }
}
