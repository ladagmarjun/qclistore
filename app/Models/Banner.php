<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $placement
 * @property string $image_url
 * @property string|null $label
 * @property string|null $headline
 * @property string|null $subtext
 * @property string $align
 * @property string|null $link_url
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 */
#[Fillable(['placement', 'image_url', 'label', 'headline', 'subtext', 'align', 'link_url', 'sort_order', 'is_active'])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** Where on the storefront a banner's slideshow appears. */
    public const PLACEMENTS = ['hero', 'middle'];

    /** Where the label, headline and subtext sit across the banner. */
    public const ALIGNMENTS = ['left', 'center', 'right'];

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
