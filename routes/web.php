<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

// The storefront is served by the JSON API in routes/api.php under /api.
Route::redirect('/', '/admin');

// Admin panel: Inertia + React pages (resources/js/pages/admin) with session auth.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'create'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    });

    Route::middleware(['auth', 'active', 'admin'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'destroy'])->name('logout');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::post('uploads/images', Admin\ImageUploadController::class)->middleware('throttle:60,1')->name('uploads.images');
        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('brands', Admin\BrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('stores', Admin\StoreController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('banners', Admin\BannerController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
        Route::resource('users', Admin\UserController::class)->only(['index', 'update']);

        Route::get('settings', [Admin\SettingController::class, 'show'])->name('settings.show');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
