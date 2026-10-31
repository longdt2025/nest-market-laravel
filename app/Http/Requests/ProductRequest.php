<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'sku' => ['nullable', 'regex:/^(?!SP[0-9]+$)[a-zA-Z0-9_.-]{1,60}$/i', Rule::unique('products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'unit' => ['nullable', 'string', 'max:30'],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'name' => ['required', 'string', 'min:3', 'max:180'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->when($this->boolean('is_active'), fn ($rule) => $rule->where('is_active', true))],
            'price' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'compare_at_price' => ['nullable', 'integer', 'gte:price', 'max:1000000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=4096,max_height=4096'],
            'is_active' => ['nullable', 'boolean'], 'is_featured' => ['nullable', 'boolean'],
            'room' => ['nullable', Rule::in(array_keys(Product::ROOMS))],
            'material' => ['nullable', 'string', 'max:180'], 'dimensions' => ['nullable', 'string', 'max:180'],
            'color' => ['nullable', 'string', 'max:100'], 'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'care_instructions' => ['nullable', 'string', 'max:2000'],
            'gallery_images' => ['nullable', 'array', 'max:6'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:max_width=4096,max_height=4096'],
            'remove_gallery' => ['nullable', 'boolean'],
        ];
    }
}
