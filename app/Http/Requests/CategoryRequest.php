<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['nullable', 'boolean']];
    }
}
