<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
| Every route here is prefixed with /api. Authenticated routes expect an
| "Authorization: Bearer <token>" header, using a token from /api/auth/login.
*/

// Auth
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('user', [AuthController::class, 'user'])->name('auth.user');
        Route::put('user', [ProfileController::class, 'update'])->name('auth.user.update');
        Route::delete('user', [ProfileController::class, 'destroy'])->name('auth.user.destroy');
        Route::put('password', [ProfileController::class, 'updatePassword'])
            ->middleware('throttle:6,1')
            ->name('auth.password.update');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});

// Storefront
Route::get('home', [CatalogController::class, 'home'])->name('home');
Route::get('categories', [CatalogController::class, 'categories'])->name('categories.index');
Route::get('brands', [CatalogController::class, 'brands'])->name('brands.index');
Route::get('banners', [CatalogController::class, 'banners'])->name('banners.index');
Route::get('stores', [CatalogController::class, 'stores'])->name('stores.index');
Route::get('settings', [CatalogController::class, 'settings'])->name('settings.show');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Checkout works for guests too; a bearer token attaches the order to the account.
Route::post('orders', [OrderController::class, 'store'])
    ->middleware(['ordering.enabled', 'throttle:10,1'])
    ->name('orders.store');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

// Admin
Route::middleware(['auth:sanctum', 'active', 'verified', 'admin'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {
        Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

        Route::apiResource('products', Admin\ProductController::class);
        Route::apiResource('categories', Admin\CategoryController::class);
        Route::apiResource('brands', Admin\BrandController::class);
        Route::apiResource('stores', Admin\StoreController::class);
        Route::apiResource('banners', Admin\BannerController::class);

        Route::apiResource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
        Route::apiResource('users', Admin\UserController::class)->only(['index', 'show', 'update']);

        Route::get('settings', [Admin\SettingController::class, 'show'])->name('settings.show');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
