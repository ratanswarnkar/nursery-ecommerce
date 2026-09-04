@extends('layouts.admin')

@section('title', 'Edit Variant - ' . $variant->sku)
@section('header_title', 'Edit Product Variant')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.index') }}">Products</a>
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.variants.index', $product) }}">Variants</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Edit ({{ $variant->sku }})</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Variant: {{ $variant->sku }}</h1>
        <p class="page-subtitle">Product: <strong>{{ $product->name }}</strong></p>
    </div>
</div>

<form method="POST" action="{{ route('admin.products.variants.update', [$product, $variant]) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Variant Specification</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="sku" class="form-label required">Variant SKU</label>
                <input type="text" id="sku" name="sku" class="form-input" value="{{ old('sku', $variant->sku) }}" required autofocus>
                @error('sku') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="barcode" class="form-label">Barcode / UPC</label>
                <input type="text" id="barcode" name="barcode" class="form-input" value="{{ old('barcode', $variant->barcode) }}">
                @error('barcode') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="price" class="form-label required">Selling Price (₹)</label>
                <input type="number" step="0.01" id="price" name="price" class="form-input" value="{{ old('price', $variant->price) }}" required min="0">
                @error('price') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="compare_at_price" class="form-label">Compare-at Price / MRP (₹)</label>
                <input type="number" step="0.01" id="compare_at_price" name="compare_at_price" class="form-input" value="{{ old('compare_at_price', $variant->compare_at_price) }}" min="0">
                <div class="form-hint">Must be greater than or equal to selling price.</div>
                @error('compare_at_price') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="cost_price" class="form-label">Cost / Procurement Price (₹)</label>
                <input type="number" step="0.01" id="cost_price" name="cost_price" class="form-input" value="{{ old('cost_price', $variant->cost_price) }}" min="0">
                @error('cost_price') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="form-group">
                <label for="weight" class="form-label">Weight (kg)</label>
                <input type="number" step="0.01" id="weight" name="weight" class="form-input" value="{{ old('weight', $variant->weight) }}" min="0">
                @error('weight') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="length" class="form-label">Length (cm)</label>
                <input type="number" step="0.01" id="length" name="length" class="form-input" value="{{ old('length', $variant->length) }}" min="0">
                @error('length') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="width" class="form-label">Width (cm)</label>
                <input type="number" step="0.01" id="width" name="width" class="form-input" value="{{ old('width', $variant->width) }}" min="0">
                @error('width') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="height" class="form-label">Height (cm)</label>
                <input type="number" step="0.01" id="height" name="height" class="form-input" value="{{ old('height', $variant->height) }}" min="0">
                @error('height') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
            <label class="form-check">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default', (string)$variant->is_default) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Default Variant for this Product</span>
            </label>

            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', (string)$variant->is_active) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Active & Purchasable</span>
            </label>
        </div>
    </div>

    <!-- Attribute Combination Selection -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Attribute Dimensions</h2>
            <span class="badge badge-neutral">Update options</span>
        </div>

        @error('attribute_value_ids') <div class="form-error" style="margin-bottom: 0.75rem;">{{ $message }}</div> @enderror

        <div class="form-grid">
            @foreach($attributes as $attr)
                <div class="form-group">
                    <label for="attr_{{ $attr->id }}" class="form-label">{{ $attr->name }}</label>
                    <select id="attr_{{ $attr->id }}" name="attribute_value_ids[]" class="form-select">
                        <option value="">— None / Not Applicable —</option>
                        @foreach($attr->values as $val)
                            <option value="{{ $val->id }}" {{ in_array($val->id, old('attribute_value_ids', $selectedAttributeValueIds)) ? 'selected' : '' }}>
                                {{ $val->label }} ({{ $val->value }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Variant</button>
    </div>
</form>
@endsection
