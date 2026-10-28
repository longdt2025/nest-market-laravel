<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'min:3', 'max:180'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'compare_at_price' => ['nullable', 'integer', 'gte:price', 'max:1000000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'is_active' => ['nullable', 'boolean'], 'is_featured' => ['nullable', 'boolean'],
            'room' => ['nullable', Rule::in(array_keys(Product::ROOMS))],
            'material' => ['nullable', 'string', 'max:180'], 'dimensions' => ['nullable', 'string', 'max:180'],
            'color' => ['nullable', 'string', 'max:100'], 'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'care_instructions' => ['nullable', 'string', 'max:2000'],
            'gallery_images' => ['nullable', 'array', 'max:6'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_gallery' => ['nullable', 'boolean'],
        ];
    }
}
