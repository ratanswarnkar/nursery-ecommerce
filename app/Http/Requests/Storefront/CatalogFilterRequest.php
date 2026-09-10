<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class CatalogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $allowedSorts = ['featured', 'price_asc', 'price_desc', 'newest', 'name_asc'];
        if ($this->has('sort') && ! in_array($this->input('sort'), $allowedSorts, true)) {
            $this->merge(['sort' => 'featured']);
        }

        if ($this->has('brand') && is_string($this->input('brand'))) {
            $this->merge(['brand' => [$this->input('brand')]]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'array'],
            'brand.*' => ['string', 'max:100'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['array'],
            'attributes.*.*' => ['integer'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', 'in:featured,price_asc,price_desc,newest,name_asc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
