<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Paginate active products for the storefront.
     *
     * @param  array{category?: string, brand?: string, tag?: string, search?: string, min_price?: numeric, max_price?: numeric, sort?: string}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->where('is_active', true)
            ->with('category')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Paginate every product, including inactive ones, for the admin panel.
     *
     * @param  array{category?: string, brand?: string, tag?: string, search?: string, min_price?: numeric, max_price?: numeric, sort?: string}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with('category')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findActiveBySlug(string $slug): Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $data['slug'] = ($data['slug'] ?? null) ?: $this->uniqueSlug((string) $data['name']);

        return Product::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        if (empty($data['slug'])) {
            unset($data['slug']);

            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = $this->uniqueSlug((string) $data['name'], $product->id);
            }
        }

        $product->update($data);

        return $product;
    }

    /**
     * Build a slug from the name, adding a numeric suffix if it's already taken.
     */
    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array{category?: string, brand?: string, tag?: string, search?: string, min_price?: numeric, max_price?: numeric, sort?: string}  $filters
     * @return Builder<Product>
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Product::query()
            // A parent category's slug also matches products filed under its subcategories.
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'category',
                fn (Builder $query) => $query->where('slug', $slug)->orWhereRelation('parent', 'slug', $slug)
            ))
            ->when($filters['brand'] ?? null, fn (Builder $query, string $brand) => $query->where('brand', $brand))
            ->when($filters['tag'] ?? null, fn (Builder $query, string $tag) => $query->where('tag', $tag))
            ->when($filters['min_price'] ?? null, fn (Builder $query, $min) => $query->where('price', '>=', $min))
            ->when($filters['max_price'] ?? null, fn (Builder $query, $max) => $query->where('price', '<=', $max))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ));

        return match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('review_count'),
            'popular' => $query->orderByDesc('review_count'),
            default => $query->latest(),
        };
    }
}
