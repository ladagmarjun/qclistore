<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property Carbon|null $created_at
 */
#[Fillable(['parent_id', 'name', 'slug', 'sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * All categories in display order, each top-level category followed by its subcategories.
     *
     * @param  (callable(Builder<Category>): mixed)|null  $scope
     * @return Collection<int, Category>
     */
    public static function tree(?callable $scope = null): Collection
    {
        $categories = static::query()
            ->when($scope, fn (Builder $query) => $scope($query))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return new Collection($categories->whereNull('parent_id')
            ->flatMap(fn (Category $parent) => [$parent, ...$categories->where('parent_id', $parent->id)])
            ->all());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
