<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Generate a slug from the name when creating without one.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->route('category') && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            // Only top-level categories can be parents, a category can't be its own parent,
            // and a category that already has subcategories must stay top-level.
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                Rule::notIn(array_filter([$category?->id])),
                function (string $attribute, mixed $value, Closure $fail) use ($category) {
                    if ($value !== null && $category?->children()->exists()) {
                        $fail(__('A category with subcategories can\'t become a subcategory.'));
                    }
                },
            ],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'required', 'string', 'max:100', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
