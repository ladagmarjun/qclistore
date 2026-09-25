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
    'leather_type', 'hardware', 'dimensions', 'colors', 'glyph', 'image_url', 'images',
    'links', 'rating', 'review_count', 'stock', 'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

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
            'images' => 'array',
            'links' => 'array',
            'rating' => 'decimal:1',
            'review_count' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
