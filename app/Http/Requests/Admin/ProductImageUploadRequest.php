<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,avif',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                'max:5120',
            ],
            'product_variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product?->id),
            ],
            'is_primary' => ['nullable', 'boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
