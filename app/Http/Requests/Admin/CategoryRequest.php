<?php

namespace App\Http\Requests\Admin;

use App\Services\Catalog\CategoryHierarchyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('category');
            $parentId = $this->input('parent_id');

            if ($category && $parentId) {
                if ((int) $parentId === (int) $category->id) {
                    $validator->errors()->add('parent_id', 'A category cannot be its own parent.');

                    return;
                }

                $hierarchyService = app(CategoryHierarchyService::class);
                if ($hierarchyService->isDescendant((int) $parentId, $category->id)) {
                    $validator->errors()->add('parent_id', 'A category cannot have one of its own subcategories or descendants as its parent.');
                }
            }
        });
    }
}
