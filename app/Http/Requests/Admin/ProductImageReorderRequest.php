<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductImage;
use Illuminate\Foundation\Http\FormRequest;

class ProductImageReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['integer', 'distinct', 'exists:product_images,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $product = $this->route('product');
            $imageIds = $this->input('image_ids', []);

            if ($product && ! empty($imageIds)) {
                $count = ProductImage::where('product_id', $product->id)
                    ->whereIn('id', $imageIds)
                    ->count();

                if ($count !== count($imageIds)) {
                    $validator->errors()->add('image_ids', 'One or more image IDs do not belong to this product.');
                }
            }
        });
    }
}
