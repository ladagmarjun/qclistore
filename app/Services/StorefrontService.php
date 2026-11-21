<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only storefront data. Every visitor sees the same lists, so the API caches
 * their JSON (see remember) and drops it after an admin change (see flushCache).
 */
class StorefrontService
{
    private const CACHE_PREFIX = 'storefront:';

    /** Upper bound on staleness if a change ever bypasses model events (e.g. a raw SQL update). */
    private const CACHE_SECONDS = 600;

    /** Every key passed to remember(), so flushCache() can clear them all. */
    public const CACHE_KEYS = ['home', 'banners:all', 'banners:hero', 'banners:middle', 'categories', 'brands', 'stores'];

    /**
     * @return Collection<int, Banner>
     */
    public function banners(?string $placement = null): Collection
    {
        return Banner::query()
            ->where('is_active', true)
            ->when($placement, fn ($query) => $query->where('placement', $placement))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::tree();
    }

    /**
     * @return Collection<int, Brand>
     */
    public function brands(): Collection
    {
        return Brand::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Store>
     */
    public function stores(): Collection
    {
        return Store::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function featuredProducts(int $limit = 8): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('tag', 'Bestseller')
            ->orderByDesc('review_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function newArrivals(int $limit = 8): Collection
    {
        return Product::query()->where('is_active', true)->latest()->limit($limit)->get();
    }

    /**
     * Cache a JSON-ready array under one of CACHE_KEYS.
     *
     * Only plain arrays go in: the cache refuses to unserialize PHP objects
     * (config/cache.php serializable_classes), so models would come back broken.
     *
     * @param  Closure(): array<array-key, mixed>  $callback
     * @return array<array-key, mixed>
     */
    public function remember(string $key, Closure $callback): array
    {
        return Cache::remember(self::CACHE_PREFIX.$key, self::CACHE_SECONDS, $callback);
    }

    /**
     * Drop every cached storefront response so the next request reads fresh data.
     */
    public function flushCache(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }
}
