<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'was_price' => $this->was_price,
            'tag' => $this->tag,
            'brand' => $this->brand,
            'leather_type' => $this->leather_type,
            'hardware' => $this->hardware,
            'dimensions' => $this->dimensions,
            'colors' => $this->colors,
            'glyph' => $this->glyph,
            'image_url' => $this->image_url,
            'images' => $this->images,
            // Cast to an object so an empty value encodes as {} rather than [].
            'links' => (object) $this->links,
            'rating' => $this->rating,
            'review_count' => $this->review_count,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
