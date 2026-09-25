<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class StorefrontService
{
    /**
     * @return Collection<int, Banner>
     */
    public function banners(): Collection
    {
        return Banner::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->orderBy('sort_order')->orderBy('name')->get();
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
}
