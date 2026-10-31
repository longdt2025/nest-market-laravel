<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => preg_replace('/\s+/u', ' ', trim($this->input('name')))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['bail', 'required', 'string', 'min:2', 'max:120', function ($attribute, $value, $fail) {
            $query = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
            if ($id = $this->route('category')?->id) {
                $query->where('id', '!=', $id);
            }
            if ($query->exists()) {
                $fail('Tên danh mục đã tồn tại.');
            }
        }], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['nullable', 'boolean']];
    }
}
