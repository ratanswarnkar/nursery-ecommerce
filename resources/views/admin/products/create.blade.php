@extends('layouts.admin')

@section('title', 'Add Product')
@section('header_title', 'Create Product')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.index') }}">Products</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Add New Product</h1>
        <p class="page-subtitle">Define catalog specifications, primary categories, and initialize the base variant.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.products.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">General Information</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Product Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus placeholder="e.g., Fiddle Leaf Fig (Ficus lyrata)">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="base_sku" class="form-label required">Base SKU</label>
                <input type="text" id="base_sku" name="base_sku" class="form-input" value="{{ old('base_sku') }}" required placeholder="e.g., FIC-LYR-001">
                <div class="form-hint">Unique master SKU identifier.</div>
                @error('base_sku') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="brand_id" class="form-label">Brand / Nursery</label>
                <select id="brand_id" name="brand_id" class="form-select">
                    <option value="">— None / In-house Cultivar —</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
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
                        <option value="{{ $tax->id }}" {{ old('tax_class_id') == $tax->id ? 'selected' : '' }}>
                            {{ $tax->name }}
                        </option>
                    @endforeach
                </select>
                @error('tax_class_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="short_description" class="form-label">Short Summary</label>
            <input type="text" id="short_description" name="short_description" class="form-input" value="{{ old('short_description') }}" placeholder="Brief highlight sentence for cards and search...">
            @error('short_description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="full_description" class="form-label">Detailed Description</label>
            <textarea id="full_description" name="full_description" class="form-textarea" rows="6" placeholder="Comprehensive plant care, dimensions, watering schedule, lighting requirements, and pot specifications...">{{ old('full_description') }}</textarea>
            @error('full_description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Active & Available in Catalog</span>
            </label>

            <label class="form-check">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') == '1' ? 'checked' : '' }}>
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

        <p style="font-size: 0.8125rem; color: var(--text-dim); margin-bottom: 1rem;">
            A product can belong to multiple categories, but exactly one must be designated as the <strong>Primary Category</strong> for canonical URL routing and breadcrumbs.
        </p>

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
                        <tr style="border-bottom: 1px solid rgba(51, 65, 85, 0.4);">
                            <td style="width: 50px; padding: 0.4rem 0;">
                                <input type="checkbox" name="category_ids[]" value="{{ $cat['id'] }}" id="cat_{{ $cat['id'] }}"
                                    class="category-checkbox"
                                    {{ in_array($cat['id'], old('category_ids', [])) ? 'checked' : '' }}
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
                                        {{ old('primary_category_id') == $cat['id'] ? 'checked' : '' }}>
                                    Primary
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Initial Base Variant -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Initial Base Pricing (Default Variant)</h2>
            <span class="badge badge-primary">SKU will match Base SKU</span>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="initial_price" class="form-label required">Selling Price (₹)</label>
                <input type="number" step="0.01" id="initial_price" name="initial_price" class="form-input" value="{{ old('initial_price') }}" required min="0" placeholder="e.g., 499.00">
                @error('initial_price') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="initial_compare_at_price" class="form-label">Compare-at Price / MRP (₹)</label>
                <input type="number" step="0.01" id="initial_compare_at_price" name="initial_compare_at_price" class="form-input" value="{{ old('initial_compare_at_price') }}" min="0" placeholder="e.g., 699.00 (>= Selling Price)">
                <div class="form-hint">Must be greater than or equal to selling price.</div>
                @error('initial_compare_at_price') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="initial_cost_price" class="form-label">Cost / Procurement Price (₹)</label>
                <input type="number" step="0.01" id="initial_cost_price" name="initial_cost_price" class="form-input" value="{{ old('initial_cost_price') }}" min="0" placeholder="e.g., 250.00">
                <div class="form-hint">Internal purchasing cost (not shown to customers).</div>
                @error('initial_cost_price') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="initial_stock" class="form-label">Initial Stock (MAIN-WH-01 Central Warehouse)</label>
                <input type="number" id="initial_stock" name="initial_stock" class="form-input" value="{{ old('initial_stock', 0) }}" min="0" placeholder="e.g., 25">
                <div class="form-hint">Physical on-hand units available for sale (leave 0 if not yet stocked).</div>
                @error('initial_stock') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="safety_stock" class="form-label">Safety Stock Threshold</label>
                <input type="number" id="safety_stock" name="safety_stock" class="form-input" value="{{ old('safety_stock', 3) }}" min="0" placeholder="e.g., 3">
                <div class="form-hint">Triggers Low Stock alert when available units drop to this level.</div>
                @error('safety_stock') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <!-- SEO Metadata Component -->
    <x-admin.seo-form />

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Product & Initialize Base Variant</button>
    </div>
</form>

@push('scripts')
<script>
    function handleCategoryToggle(catId) {
        const checkbox = document.getElementById('cat_' + catId);
        const radio = document.getElementById('primary_' + catId);

        if (checkbox.checked) {
            // If no primary radio is checked yet, automatically check this one
            const hasCheckedPrimary = document.querySelector('input[name="primary_category_id"]:checked');
            if (!hasCheckedPrimary) {
                radio.checked = true;
            }
        } else {
            if (radio.checked) {
                radio.checked = false;
                // Automatically find first other checked category and make primary
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
