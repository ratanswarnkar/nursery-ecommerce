@extends('layouts.admin')

@section('title', 'Products')
@section('header_title', 'Product Catalog')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Products</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Product Catalog</h1>
        <p class="page-subtitle">Manage plant species, planters, tools, soil, and nursery inventory offerings.</p>
    </div>
    <div>
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Product</span>
            </a>
        @endcan
    </div>
</div>

<!-- Search & Filters -->
<div class="card" style="padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('admin.products.index') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="form-input" placeholder="Search by name or SKU..." value="{{ request('search') }}">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="category_id" class="form-select">
                <option value="">— All Categories —</option>
                @foreach($categoriesTree as $cat)
                    <option value="{{ $cat['id'] }}" {{ request('category_id') == $cat['id'] ? 'selected' : '' }}>
                        {{ $cat['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <select name="brand_id" class="form-select">
                <option value="">— All Brands —</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="flex: 1; min-width: 120px;">
            <select name="status" class="form-select">
                <option value="">— Any Status —</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request()->hasAny(['search', 'category_id', 'brand_id', 'status', 'is_featured']))
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary" title="Reset Filters">Reset</a>
            @endif
        </div>
    </form>
</div>

@if($products->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        <div class="empty-title">No products found</div>
        <div class="empty-desc">No catalog products match the specified criteria or no products have been added yet.</div>
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Create First Product</a>
        @endcan
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">Image</th>
                    <th>Product & Base SKU</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Variants</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    @php
                        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
                        $minPrice = $product->variants->min('price');
                        $maxPrice = $product->variants->max('price');
                        $primaryCategory = $product->categories->firstWhere('pivot.is_primary', true) ?? $product->categories->first();
                    @endphp
                    <tr>
                        <td>
                            @if($product->primaryImage)
                                <img src="{{ $product->primaryImage->url }}" alt="{{ $product->name }}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                            @else
                                <div style="width: 44px; height: 44px; background: #1a2333; border-radius: 4px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-dim); font-size: 0.65rem;">
                                    No Pic
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600;">
                                <a href="{{ route('admin.products.show', $product) }}" style="color: var(--text-main); text-decoration: none;">
                                    {{ $product->name }}
                                </a>
                                @if($product->is_featured)
                                    <span class="badge badge-warning" style="font-size: 0.65rem; margin-left: 0.25rem;">Featured</span>
                                @endif
                            </div>
                            <div style="font-family: monospace; font-size: 0.75rem; color: var(--primary-light); margin-top: 0.15rem;">
                                SKU: {{ $product->base_sku }}
                            </div>
                        </td>
                        <td>
                            {{ $product->brand?->name ?? '—' }}
                        </td>
                        <td>
                            @if($primaryCategory)
                                <span class="badge badge-neutral">{{ $primaryCategory->name }}</span>
                            @else
                                <span style="color: var(--text-dim); font-size: 0.75rem;">None</span>
                            @endif
                        </td>
                        <td style="font-weight: 600;">
                            @if($minPrice !== null)
                                @if($minPrice === $maxPrice)
                                    ₹{{ number_format((float)$minPrice, 2) }}
                                @else
                                    ₹{{ number_format((float)$minPrice, 2) }} - ₹{{ number_format((float)$maxPrice, 2) }}
                                @endif
                            @else
                                <span style="color: var(--text-dim); font-size: 0.75rem;">No Variants</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.products.variants.index', $product) }}" class="badge badge-primary" style="text-decoration: none;">
                                {{ $product->variants->count() }} Variants
                            </a>
                        </td>
                        <td>
                            @can('products.update', 'admin')
                                <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="background: none; border: none; cursor: pointer;">
                                        @if($product->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                @if($product->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            @endcan
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary btn-sm" title="View Full Details">View</a>
                            @can('products.update', 'admin')
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary btn-sm" title="Edit Product">Edit</a>
                            @endcan
                            @can('products.delete', 'admin')
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="openConfirmModal('{{ route('admin.products.destroy', $product) }}', 'Are you sure you want to delete product \'{{ addslashes($product->name) }}\'? If historical orders reference it, it will be soft-deleted.', 'Delete Product')">
                                    Delete
                                </button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $products->links() }}
    </div>
@endif
@endsection
