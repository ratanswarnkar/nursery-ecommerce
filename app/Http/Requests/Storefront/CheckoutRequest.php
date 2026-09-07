<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['required', 'integer'],
            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'billing_address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address_id.required' => 'Please select a delivery address to complete checkout.',
            'shipping_address_id.integer' => 'Invalid delivery address selection.',
        ];
    }
}
