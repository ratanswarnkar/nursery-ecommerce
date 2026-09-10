<?php

namespace App\Http\Requests\Storefront;

use App\Models\CustomerAddress;
use App\Services\Shipping\DelhiNcrEligibilityService;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $shippingAddressId = $this->input('shipping_address_id');
            if ($shippingAddressId) {
                $customer = auth('customer')->user();
                $address = CustomerAddress::where('customer_id', $customer?->id)
                    ->find($shippingAddressId);

                if ($address && ! app(DelhiNcrEligibilityService::class)->isEligible($address)) {
                    $validator->errors()->add(
                        'shipping_address_id',
                        'Delivery is currently available only within Delhi NCR. Please select an address within Delhi NCR.'
                    );
                }
            }
        });
    }
}
