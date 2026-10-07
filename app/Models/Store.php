<?php

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string|null $barangay
 * @property string|null $city
 * @property string|null $region
 * @property string $hours
 * @property string|null $map_url
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 */
#[Fillable(['name', 'address', 'barangay', 'city', 'region', 'hours', 'map_url', 'sort_order', 'is_active'])]
class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
