<?php

namespace App\Http\Requests;

class CheckoutRequest extends DeliveryDetailsRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return parent::rules() + ['checkout_token' => ['required', 'uuid'], 'save_address' => ['nullable', 'boolean']];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Số điện thoại cần có 10 số (bắt đầu bằng 0) hoặc +84 và 9 số.'];
    }
}
