@extends('layouts.admin')

@section('title', $product->name)
@section('header_title', 'Product Overview')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.index') }}">Products</a>
    <span class="breadcrumbs-sep">/</span>
    <span>{{ $product->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <h1 class="page-title">{{ $product->name }}</h1>
            @if($product->is_active)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-danger">Inactive</span>
            @endif
            @if($product->is_featured)
                <span class="badge badge-warning">Featured</span>
            @endif
        </div>
        <div style="font-family: monospace; color: var(--primary-light); font-size: 0.875rem; margin-top: 0.25rem;">
            Base SKU: {{ $product->base_sku }} | Slug: {{ $product->slug }}
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        @can('products.update', 'admin')
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-secondary">Edit Details</a>
        @endcan
        <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">
            Manage Variants ({{ $product->variants->count() }})
        </a>
        <a href="{{ route('admin.products.images.index', $product) }}" class="btn btn-secondary">
            Manage Images ({{ $product->images->count() }})
        </a>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 2fr 1fr; align-items: start;">
    <div>
        <!-- Summary & Descriptions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Descriptions & Metadata</h2>
            </div>

            @if($product->short_description)
                <div style="margin-bottom: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Summary</div>
                    <div style="font-size: 0.925rem; color: var(--text-main); margin-top: 0.25rem;">{{ $product->short_description }}</div>
                </div>
            @endif

            <div>
                <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Full Description</div>
                <div style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; margin-top: 0.25rem; white-space: pre-line;">
                    {{ $product->full_description ?: 'No detailed description provided.' }}
                </div>
            </div>
        </div>

        <!-- Variants Matrix -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Purchasable Variants ({{ $product->variants->count() }})</h2>
                @can('products.create', 'admin')
                    <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-primary btn-sm">+ Add Variant</a>
                @endcan
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Variant SKU</th>
                            <th>Attributes</th>
                            <th>Selling Price</th>
                            <th>MRP / Compare</th>
                            <th>Status</th>
                            <th>Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->variants as $variant)
                            <tr>
                                <td style="font-family: monospace; font-weight: 600; color: var(--text-main);">
                                    {{ $variant->sku }}
                                </td>
                                <td>
                                    @forelse($variant->attributeValues as $val)
                                        <span class="badge badge-neutral" style="font-size: 0.7rem;">
                                            {{ $val->attribute->name }}: {{ $val->label }}
                                        </span>
                                    @empty
                                        <span style="color: var(--text-dim); font-size: 0.75rem;">Base Product</span>
                                    @endforelse
                                </td>
                                <td style="font-weight: 600; color: #34d399;">
                                    ₹{{ number_format((float)$variant->price, 2) }}
                                </td>
                                <td style="color: var(--text-dim);">
                                    {{ $variant->compare_at_price ? '₹' . number_format((float)$variant->compare_at_price, 2) : '—' }}
                                </td>
                                <td>
                                    @if($variant->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($variant->is_default)
                                        <span class="badge badge-primary">Default</span>
                                    @else
                                        <span style="color: var(--text-dim); font-size: 0.75rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Info -->
    <div>
        <!-- Classification -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Classification</h2>
            </div>

            <div style="font-size: 0.875rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <div>
                    <div style="color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">Brand</div>
                    <div style="font-weight: 600; margin-top: 0.15rem;">{{ $product->brand?->name ?? 'None / Unbranded' }}</div>
                </div>

                <div>
                    <div style="color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">Tax Class</div>
                    <div style="font-weight: 600; margin-top: 0.15rem;">{{ $product->taxClass?->name ?? 'Standard' }}</div>
                </div>

                <div>
                    <div style="color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">Assigned Categories</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.25rem;">
                        @foreach($product->categories as $cat)
                            <span class="badge {{ $cat->pivot->is_primary ? 'badge-primary' : 'badge-neutral' }}">
                                {{ $cat->name }} {{ $cat->pivot->is_primary ? '★ Primary' : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Preview -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Gallery ({{ $product->images->count() }})</h2>
                @can('products.update', 'admin')
                    <a href="{{ route('admin.products.images.index', $product) }}" class="btn btn-secondary btn-sm">Upload</a>
                @endcan
            </div>

            @if($product->images->isEmpty())
                <div style="text-align: center; padding: 1.5rem; color: var(--text-dim); font-size: 0.8125rem;">
                    No images uploaded yet.
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                    @foreach($product->images as $img)
                        <div style="position: relative; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border-color);">
                            <img src="{{ $img->url }}" alt="{{ $img->alt_text }}" style="width: 100%; height: 80px; object-fit: cover;">
                            @if($img->is_primary)
                                <span class="badge badge-primary" style="position: absolute; bottom: 2px; left: 2px; font-size: 0.65rem; padding: 1px 4px;">
                                    Primary
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
