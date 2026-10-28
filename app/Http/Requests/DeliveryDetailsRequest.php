<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $this->user() !== null && (! $order || (int) $order->user_id === (int) $this->user()->id);
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'regex:/^(0[0-9]{9}|\+84[0-9]{9})$/'],
            'shipping_address' => ['required', 'string', 'min:10', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Số điện thoại cần có 10 số (bắt đầu bằng 0) hoặc +84 và 9 số.'];
    }
}
