<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProductRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }
    public function rules(): array
    {
        $product = $this->route('product');
        return [
            'name'=>['required','string','min:3','max:180'],
            'category_id'=>['required','exists:categories,id'],
            'price'=>['required','integer','min:1000','max:1000000000'],
            'compare_at_price'=>['nullable','integer','gte:price','max:1000000000'],
            'stock'=>['required','integer','min:0','max:1000000'],
            'description'=>['nullable','string','max:5000'],
            'image'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:3072'],
            'is_active'=>['nullable','boolean'], 'is_featured'=>['nullable','boolean'],
        ];
    }
}
