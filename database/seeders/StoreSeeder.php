<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Seed sample physical store locations.
     */
    public function run(): void
    {
        Store::factory()->count(4)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
    }
}
