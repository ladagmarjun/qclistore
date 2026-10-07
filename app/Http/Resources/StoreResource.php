<?php

namespace App\Http\Resources;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A physical store location.
 *
 * @mixin Store
 */
class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'barangay' => $this->barangay,
            'city' => $this->city,
            'region' => $this->region,
            'hours' => $this->hours,
            'map_url' => $this->map_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
