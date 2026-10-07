<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'placement' => ['sometimes', 'required', Rule::in(Banner::PLACEMENTS)],
            'image_url' => ['required', 'url', 'max:500'],
            'headline' => ['nullable', 'string', 'max:200'],
            'subtext' => ['nullable', 'string', 'max:300'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
