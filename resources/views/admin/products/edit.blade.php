@extends('layouts.admin')

@section('title', 'Edit Product - ' . $product->name)
@section('header_title', 'Edit Product')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.index') }}">Products</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Edit ({{ $product->name }})</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Product: {{ $product->name }}</h1>
        <p class="page-subtitle">Update core descriptions, brand, tax classes, categories, and SEO.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">
            Manage Variants ({{ $product->variants->count() }})
        </a>
        <a href="{{ route('admin.products.images.index', $product) }}" class="btn btn-secondary">
            Manage Images ({{ $product->images->count() }})
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">General Information</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Product Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $product->name) }}" required autofocus>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="base_sku" class="form-label required">Base SKU</label>
                <input type="text" id="base_sku" name="base_sku" class="form-input" value="{{ old('base_sku', $product->base_sku) }}" required>
                @error('base_sku') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="brand_id" class="form-label">Brand / Nursery</label>
                <select id="brand_id" name="brand_id" class="form-select">
                    <option value="">— None / In-house Cultivar —</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
                @error('brand_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="tax_class_id" class="form-label">Tax Class (GST)</label>
                <select id="tax_class_id" name="tax_class_id" class="form-select">
                    <option value="">— Default Standard Tax —</option>
                    @foreach($taxClasses as $tax)
                        <option value="{{ $tax->id }}" {{ old('tax_class_id', $product->tax_class_id) == $tax->id ? 'selected' : '' }}>
                            {{ $tax->name }}
                        </option>
                    @endforeach
                </select>
                @error('tax_class_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="short_description" class="form-label">Short Summary</label>
            <input type="text" id="short_description" name="short_description" class="form-input" value="{{ old('short_description', $product->short_description) }}">
            @error('short_description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="full_description" class="form-label">Detailed Description</label>
            <textarea id="full_description" name="full_description" class="form-textarea" rows="6">{{ old('full_description', $product->full_description) }}</textarea>
            @error('full_description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', (string)$product->is_active) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Active & Available in Catalog</span>
            </label>

            <label class="form-check">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', (string)$product->is_featured) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Mark as Featured Product</span>
            </label>
        </div>
    </div>

    <!-- Category Mapping & Primary Assignment -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Category Assignment</h2>
            <span class="badge badge-neutral">Select categories & pick 1 primary</span>
        </div>

        @error('category_ids') <div class="form-error" style="margin-bottom: 0.75rem;">{{ $message }}</div> @enderror
        @error('primary_category_id') <div class="form-error" style="margin-bottom: 0.75rem;">{{ $message }}</div> @enderror

        <div style="max-height: 240px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 0.75rem; background: var(--bg-main);">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-dim); font-size: 0.75rem;">
                        <th style="text-align: left; padding-bottom: 0.5rem;">Assign</th>
                        <th style="text-align: left; padding-bottom: 0.5rem;">Category Hierarchy</th>
                        <th style="text-align: right; padding-bottom: 0.5rem;">Primary Selection</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoriesTree as $cat)
                        @php
                            $isAssigned = in_array($cat['id'], old('category_ids', $selectedCategoryIds));
                            $isPrimary = old('primary_category_id', $primaryCategoryId) == $cat['id'];
                        @endphp
                        <tr style="border-bottom: 1px solid rgba(51, 65, 85, 0.4);">
                            <td style="width: 50px; padding: 0.4rem 0;">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat['id'] }}" id="cat_{{ $cat['id'] }}"
                                    class="category-checkbox"
                                    {{ $isAssigned ? 'checked' : '' }}
                                    onchange="handleCategoryToggle({{ $cat['id'] }})">
                            </td>
                            <td>
                                <label for="cat_{{ $cat['id'] }}" style="cursor: pointer; color: var(--text-main);">
                                    {{ $cat['name'] }}
                                </label>
                            </td>
                            <td style="text-align: right;">
                                <label style="cursor: pointer; font-size: 0.75rem; color: var(--text-muted);">
                                    <input type="radio" name="primary_category_id" value="{{ $cat['id'] }}" id="primary_{{ $cat['id'] }}"
                                        {{ $isPrimary ? 'checked' : '' }}>
                                    Primary
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEO Metadata Component -->
    <x-admin.seo-form :seo="$product->seoMetadata" />

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Product</button>
    </div>
</form>

@push('scripts')
<script>
    function handleCategoryToggle(catId) {
        const checkbox = document.getElementById('cat_' + catId);
        const radio = document.getElementById('primary_' + catId);

        if (checkbox.checked) {
            const hasCheckedPrimary = document.querySelector('input[name="primary_category_id"]:checked');
            if (!hasCheckedPrimary) {
                radio.checked = true;
            }
        } else {
            if (radio.checked) {
                radio.checked = false;
                const firstChecked = document.querySelector('.category-checkbox:checked');
                if (firstChecked) {
                    const firstId = firstChecked.value;
                    document.getElementById('primary_' + firstId).checked = true;
                }
            }
        }
    }
</script>
@endpush
@endsection
