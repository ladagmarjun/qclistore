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

test('in production only the admin is seeded, with the password from the config', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.admin.password' => 'a-strong-password']);

    app(UserSeeder::class)->run();

    $admin = User::query()->sole();
    expect($admin->email)->toBe('admin@peacock.com')
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('a-strong-password', $admin->password))->toBeTrue();
});

test('in production the admin is not seeded without a password', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.admin.password' => null]);

    expect(fn () => app(UserSeeder::class)->run())->toThrow(RuntimeException::class);
    expect(User::query()->count())->toBe(0);
});
