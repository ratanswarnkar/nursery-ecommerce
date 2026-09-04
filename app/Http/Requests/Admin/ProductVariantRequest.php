<?php

namespace App\Http\Requests\Admin;

use App\Enums\AttributeType;
use App\Models\AttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variantId),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'gte:price',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'cost_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'distinct', 'exists:attribute_values,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $attributeValueIds = $this->input('attribute_value_ids', []);
            if (empty($attributeValueIds)) {
                return;
            }

            $values = AttributeValue::with('attribute')->whereIn('id', $attributeValueIds)->get();

            // 1. Enforce that only select and multiselect attributes may have values in the variant pivot
            foreach ($values as $val) {
                if (! in_array($val->attribute->type, [AttributeType::SELECT, AttributeType::MULTISELECT], true)) {
                    $validator->errors()->add(
                        'attribute_value_ids',
                        "Attribute '{$val->attribute->name}' does not support selectable options. Values for boolean, text, or numeric attributes belong in custom attributes."
                    );
                }
            }

            // 2. Enforce maximum 1 value per attribute dimension for a variant
            $dimensionCounts = $values->groupBy('attribute_id')->map->count();
            foreach ($dimensionCounts as $attrId => $count) {
                if ($count > 1) {
                    $attrName = $values->firstWhere('attribute_id', $attrId)?->attribute?->name ?? 'Attribute';
                    $validator->errors()->add(
                        'attribute_value_ids',
                        "A variant cannot have more than one value for the '{$attrName}' attribute."
                    );
                }
            }
        });
    }
}
