<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'adjustment_mode' => ['required', 'string', 'in:delta,set'],
            'quantity' => ['required', 'integer'],
            'type' => ['nullable', 'string', Rule::enum(StockMovementType::class)],
            'notes' => ['nullable', 'string', 'max:500'],
            'safety_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
