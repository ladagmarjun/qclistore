<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\StorefrontService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureStorefrontCache();
        $this->configureRateLimiting();

        // Reset links open the frontend, which posts the token back to /api/auth/reset-password.
        ResetPassword::createUrlUsing(fn (User $user, string $token): string => rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]));
    }

    /**
     * Clear the cached storefront lists whenever the data behind them changes,
     * including stock changes from orders. Waits for the transaction to commit so a
     * concurrent request can't re-cache the old rows.
     */
    protected function configureStorefrontCache(): void
    {
        $flush = fn () => DB::afterCommit(fn () => $this->app->make(StorefrontService::class)->flushCache());

        foreach ([Banner::class, Brand::class, Category::class, Product::class, Store::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }

    /**
     * Cap public storefront reads per visitor IP, so one script or scraper can't
     * use up every PHP worker. Change it with STOREFRONT_RATE_LIMIT in .env.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('storefront', fn (Request $request) => Limit::perMinute((int) config('app.storefront_rate_limit'))
            ->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
