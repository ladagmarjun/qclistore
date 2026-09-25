<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string|null $was_price
 * @property string|null $tag
 * @property string|null $brand
 * @property string|null $leather_type
 * @property string|null $hardware
 * @property string|null $dimensions
 * @property array<int, string> $colors
 * @property array<string, int> $color_stock
 * @property string $glyph
 * @property string|null $image_url
 * @property array<int, array{url: string, color: string|null}> $images
 * @property array<string, string> $links
 * @property string $rating
 * @property int $review_count
 * @property int $stock
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'category_id', 'name', 'slug', 'description', 'price', 'was_price', 'tag', 'brand',
    'leather_type', 'hardware', 'dimensions', 'colors', 'color_stock', 'glyph', 'image_url', 'images',
    'links', 'rating', 'review_count', 'stock', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * A product with colours tracks stock per colour, and its total stock is always their sum.
     * When no per-colour stock is given (a new product, or colours added to one without them), the total is split evenly.
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $colors = array_values(array_unique($product->colors ?? []));

            if ($colors === []) {
                $product->color_stock = [];

                return;
            }

            $current = $product->color_stock ?? [];

            if ($current === []) {
                $total = max(0, (int) $product->stock);
                $share = intdiv($total, count($colors));
                $current = array_fill_keys($colors, $share);
                $current[$colors[0]] += $total - $share * count($colors);
            }

            $colorStock = [];
            foreach ($colors as $color) {
                $colorStock[$color] = max(0, (int) ($current[$color] ?? 0));
            }

            $product->color_stock = $colorStock;
            $product->stock = array_sum($colorStock);
        });
    }

    /**
     * Units available in the given colour, or in total for a product without colours.
     */
    public function stockFor(?string $color): int
    {
        return $this->colors ? (int) ($this->color_stock[$color] ?? 0) : $this->stock;
    }

    /**
     * Take units out of stock (or put them back with a negative quantity). Call save() afterwards.
     * Returned units of a colour the product no longer has go to its first colour so they aren't lost.
     */
    public function adjustStock(?string $color, int $quantity): void
    {
        if (! $this->colors) {
            $this->stock -= $quantity;

            return;
        }

        $colorStock = $this->color_stock ?? [];
        $key = array_key_exists((string) $color, $colorStock) ? (string) $color : $this->colors[0];
        $colorStock[$key] = ($colorStock[$key] ?? 0) - $quantity;
        $this->color_stock = $colorStock;
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'was_price' => 'decimal:2',
            'colors' => 'array',
            'color_stock' => 'array',
            'images' => 'array',
            'links' => 'array',
            'rating' => 'decimal:1',
            'review_count' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
