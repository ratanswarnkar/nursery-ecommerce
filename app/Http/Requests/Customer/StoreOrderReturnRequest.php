<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    /**
     * Normalize items input so whether it comes as array of items or checked items map,
     * it validates cleanly.
     */
    protected function prepareForValidation(): void
    {
        // Handle selected_items + quantities map
        if ($this->has('selected_items') && is_array($this->input('selected_items'))) {
            $normalized = [];
            $quantities = $this->input('quantities', []);

            foreach ($this->input('selected_items') as $itemId) {
                $qty = isset($quantities[$itemId]) ? (int) $quantities[$itemId] : 1;
                $normalized[] = [
                    'order_item_id' => (int) $itemId,
                    'quantity' => $qty,
                ];
            }

            $this->merge(['items' => $normalized]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for the return request.',
            'items.required' => 'Please select at least one item to return.',
            'items.min' => 'Please select at least one item to return.',
            'items.*.quantity.min' => 'Return quantity must be at least 1.',
        ];
    }

    /**
     * Get the sanitized items data.
     *
     * @return array<int, array{order_item_id: int, quantity: int}>
     */
    public function getItemsData(): array
    {
        return (array) $this->input('items', []);
    }
}
