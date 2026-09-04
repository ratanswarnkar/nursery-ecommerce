@extends('layouts.admin')

@section('title', 'Variants - ' . $product->name)
@section('header_title', 'Product Variants')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.index') }}">Products</a>
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Variants</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Variants: {{ $product->name }}</h1>
        <p class="page-subtitle">Manage purchasable SKUs, prices, specifications, and default variant selection.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary">Back to Product</a>
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Variant</span>
            </a>
        @endcan
    </div>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Variant SKU</th>
                <th>Attributes / Options</th>
                <th>Price (₹)</th>
                <th>Compare-at (₹)</th>
                <th>Cost (₹)</th>
                <th>Dimensions / Weight</th>
                <th>Status</th>
                <th>Default</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($variants as $variant)
                <tr>
                    <td style="font-family: monospace; font-weight: 600; color: var(--text-main);">
                        {{ $variant->sku }}
                        @if($variant->barcode)
                            <div style="font-size: 0.75rem; color: var(--text-dim);">UPC: {{ $variant->barcode }}</div>
                        @endif
                    </td>
                    <td>
                        @forelse($variant->attributeValues as $val)
                            <span class="badge badge-neutral" style="margin-right: 0.25rem;">
                                {{ $val->attribute->name }}: <strong>{{ $val->label }}</strong>
                            </span>
                        @empty
                            <span style="color: var(--text-dim); font-size: 0.75rem;">Standard Base Option</span>
                        @endforelse
                    </td>
                    <td style="font-weight: 600; color: #34d399;">
                        ₹{{ number_format((float)$variant->price, 2) }}
                    </td>
                    <td style="color: var(--text-dim);">
                        {{ $variant->compare_at_price ? '₹' . number_format((float)$variant->compare_at_price, 2) : '—' }}
                    </td>
                    <td style="color: var(--text-dim); font-size: 0.8125rem;">
                        {{ $variant->cost_price ? '₹' . number_format((float)$variant->cost_price, 2) : '—' }}
                    </td>
                    <td style="font-size: 0.8125rem; color: var(--text-muted);">
                        @if($variant->weight) {{ $variant->weight }} kg @endif
                        @if($variant->length || $variant->width || $variant->height)
                            ({{ $variant->length ?? '-' }}×{{ $variant->width ?? '-' }}×{{ $variant->height ?? '-' }} cm)
                        @endif
                        @if(! $variant->weight && ! $variant->length) — @endif
                    </td>
                    <td>
                        @can('products.update', 'admin')
                            <form method="POST" action="{{ route('admin.products.variants.toggle-status', [$product, $variant]) }}" style="display: inline;">
                                @csrf
                                <button type="submit" style="background: none; border: none; cursor: pointer;">
                                    @if($variant->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </button>
                            </form>
                        @else
                            @if($variant->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        @endcan
                    </td>
                    <td>
                        @if($variant->is_default)
                            <span class="badge badge-primary">★ Default</span>
                        @else
                            <span style="color: var(--text-dim); font-size: 0.75rem;">—</span>
                        @endif
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        @can('products.update', 'admin')
                            <a href="{{ route('admin.products.variants.edit', [$product, $variant]) }}" class="btn btn-secondary btn-sm">Edit</a>
                        @endcan

                        @can('products.delete', 'admin')
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="openConfirmModal('{{ route('admin.products.variants.destroy', [$product, $variant]) }}', 'Are you sure you want to delete variant \'{{ addslashes($variant->sku) }}\'? If this is the default variant, another variant will be promoted.', 'Delete Variant')">
                                Delete
                            </button>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
