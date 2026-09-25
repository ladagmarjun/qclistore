<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed the default store settings.
     */
    public function run(): void
    {
        Setting::query()->updateOrCreate(['key' => 'cart_enabled'], ['value' => 'true']);
    }
}
