<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $isCreate = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'base_sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'base_sku')->ignore($productId),
            ],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'tax_class_id' => ['nullable', 'integer', 'exists:tax_classes,id'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'full_description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'primary_category_id' => ['required', 'integer'],

            // Initial price fields on creation
            'initial_price' => [
                Rule::requiredIf($isCreate),
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'initial_compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'gte:initial_price',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'initial_cost_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'initial_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'safety_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],

            // SEO fields
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryIds = $this->input('category_ids', []);
            $primaryId = $this->input('primary_category_id');

            if (! in_array((int) $primaryId, array_map('intval', $categoryIds), true)) {
                $validator->errors()->add('primary_category_id', 'The designated primary category must be one of the selected product categories.');
            }
        });
    }
}
