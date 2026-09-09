<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Controller checks RBAC orders.update
    }

    public function rules(): array
    {
        return [
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:500'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_url.regex' => 'The tracking URL must begin with http:// or https://.',
            'tracking_url.url' => 'The tracking URL must be a valid URL.',
        ];
    }
}
