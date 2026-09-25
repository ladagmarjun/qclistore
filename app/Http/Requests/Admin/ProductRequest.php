<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'was_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'tag' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:120'],
            'leather_type' => ['nullable', 'string', 'max:100'],
            'hardware' => ['nullable', 'string', 'max:100'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'colors' => ['sometimes', 'array'],
            'colors.*' => ['string', 'max:30'],
            // Stock per colour as [{color, stock}]; when sent, the product's total stock is their sum.
            'color_stock' => ['sometimes', 'array'],
            'color_stock.*.color' => ['required', 'string', 'max:30'],
            'color_stock.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'glyph' => ['nullable', 'string', 'max:10'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'images' => ['sometimes', 'array'],
            'images.*.url' => ['required', 'url', 'max:500'],
            'images.*.color' => ['nullable', 'string', 'max:30'],
            'links' => ['sometimes', 'array:shopee,lazada,tiktok'],
            'links.*' => ['nullable', 'url', 'max:500'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required_without:color_stock', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The validated data, with nulls replaced for columns that can't be null.
     *
     * @return array<string, mixed>
     */
    public function productData(): array
    {
        $data = $this->validated();

        $defaults = ['glyph' => '👜', 'rating' => 5.0, 'review_count' => 0];

        foreach ($defaults as $key => $default) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = $default;
            }
        }

        if (isset($data['links'])) {
            $data['links'] = array_filter($data['links']);
        }

        // Without per-colour numbers, a total stock is split evenly across the colours (see Product::booted()).
        if (array_key_exists('color_stock', $data)) {
            $data['color_stock'] = array_column($data['color_stock'], 'stock', 'color');
        } elseif (array_key_exists('stock', $data)) {
            $data['color_stock'] = [];
        }

        return $data;
    }
}
