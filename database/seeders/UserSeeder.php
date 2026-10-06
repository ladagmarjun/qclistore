<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Seed the admin account and, outside production, a test customer and a handful of sample customers.
     */
    public function run(): void
    {
        $this->seedAdmin();

        // Sample customers come from the factory, which needs Faker; production installs without dev packages.
        if (app()->isProduction()) {
            return;
        }

        // Safe to run again: the sample customers are only added once.
        if (User::query()->where('email', 'test@peacock.com')->doesntExist()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@peacock.com',
            ]);

            User::factory(10)->create();
        }
    }

    /**
     * Create the admin account from config/app.php (ADMIN_* in .env), leaving an existing one untouched.
     */
    private function seedAdmin(): void
    {
        $config = config('app.admin');

        if (User::query()->where('email', $config['email'])->exists()) {
            return;
        }

        $password = $config['password'];

        if (blank($password)) {
            if (app()->isProduction()) {
                throw new RuntimeException('Set ADMIN_PASSWORD in .env before seeding the admin account in production.');
            }

            $password = 'password';
        }

        $admin = new User(['name' => $config['name'], 'email' => $config['email'], 'password' => $password]);
        $admin->forceFill(['email_verified_at' => now()])->save();

        $admin->assignRole('admin');
    }
}
