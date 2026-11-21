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
 * Read-only storefront data. Every visitor sees the same lists, so each one is cached
 * and rebuilt only after an admin change (see flushCache) or when the cache expires.
 */
class StorefrontService
{
    private const CACHE_PREFIX = 'storefront:';

    /** Upper bound on staleness if a change ever bypasses model events (e.g. a raw SQL update). */
    private const CACHE_SECONDS = 600;

    /**
     * @return Collection<int, Banner>
     */
    public function banners(?string $placement = null): Collection
    {
        return $this->remember('banners:'.($placement ?? 'all'), fn () => Banner::query()
            ->where('is_active', true)
            ->when($placement, fn ($query) => $query->where('placement', $placement))
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return $this->remember('categories', fn () => Category::tree());
    }

    /**
     * @return Collection<int, Brand>
     */
    public function brands(): Collection
    {
        return $this->remember('brands', fn () => Brand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get());
    }

    /**
     * @return Collection<int, Store>
     */
    public function stores(): Collection
    {
        return $this->remember('stores', fn () => Store::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * @return Collection<int, Product>
     */
    public function featuredProducts(): Collection
    {
        return $this->remember('featured', fn () => Product::query()
            ->where('is_active', true)
            ->where('tag', 'Bestseller')
            ->orderByDesc('review_count')
            ->limit(8)
            ->get());
    }

    /**
     * @return Collection<int, Product>
     */
    public function newArrivals(): Collection
    {
        return $this->remember('new_arrivals', fn () => Product::query()
            ->where('is_active', true)
            ->latest()
            ->limit(8)
            ->get());
    }

    /**
     * Drop every cached storefront list so the next request reads fresh data.
     */
    public function flushCache(): void
    {
        foreach (self::cacheKeys() as $key) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }
    }

    /**
     * @return list<string>
     */
    private static function cacheKeys(): array
    {
        return [
            'banners:all',
            ...array_map(fn (string $placement) => "banners:{$placement}", Banner::PLACEMENTS),
            'categories',
            'brands',
            'stores',
            'featured',
            'new_arrivals',
        ];
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(self::CACHE_PREFIX.$key, self::CACHE_SECONDS, $callback);
    }
}
