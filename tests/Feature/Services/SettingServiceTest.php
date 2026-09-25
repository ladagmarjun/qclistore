<?php

use App\Services\SettingService;

test('the cart is enabled by default', function () {
    expect(app(SettingService::class)->cartEnabled())->toBeTrue();
});

test('updating a setting clears the cached value', function () {
    $settings = app(SettingService::class);

    expect($settings->cartEnabled())->toBeTrue();

    $settings->set('cart_enabled', 'false');

    expect($settings->cartEnabled())->toBeFalse()
        ->and($settings->get('cart_enabled'))->toBe('false');
});
