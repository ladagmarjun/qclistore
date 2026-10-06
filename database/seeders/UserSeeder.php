<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed an admin, a test customer and a handful of sample customers. Every account's password is "password".
     */
    public function run(): void
    {
        // Safe to run again: existing accounts are left alone and the sample customers are only added once.
        if (User::query()->where('email', 'admin@peacock.com')->doesntExist()) {
            User::factory()->admin()->create([
                'name' => 'Admin User',
                'email' => 'admin@peacock.com',
            ]);
        }

        if (User::query()->where('email', 'test@peacock.com')->doesntExist()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@peacock.com',
            ]);

            User::factory(10)->create();
        }
    }
}
