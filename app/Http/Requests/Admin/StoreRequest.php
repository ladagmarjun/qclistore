<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a physical store location (not "store this record").
 */
class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'barangay' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:150'],
            'region' => ['required', 'string', 'max:150'],
            'hours' => ['sometimes', 'required', 'string', 'max:150'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
