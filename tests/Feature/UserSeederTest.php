<?php

use App\Models\User;
use Database\Seeders\UserSeeder;

test('the user seeder creates an admin and customers', function () {
    $this->seed(UserSeeder::class);

    expect(User::query()->where('email', 'admin@peacock.com')->sole()->hasRole('admin'))->toBeTrue()
        ->and(User::query()->where('email', 'test@peacock.com')->sole()->hasRole('customer'))->toBeTrue()
        ->and(User::query()->count())->toBe(12);
});

test('the user seeder can run again without duplicating accounts', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::query()->count())->toBe(12);
});
