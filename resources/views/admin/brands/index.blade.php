@extends('layouts.admin')

@section('title', 'Brands')
@section('header_title', 'Brand Management')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Brands</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Brands & Manufacturers</h1>
        <p class="page-subtitle">Manage plant cultivators, pot designers, fertilizer brands, and tool manufacturers.</p>
    </div>
    <div>
        @can('brands.create', 'admin')
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Brand</span>
            </a>
        @endcan
    </div>
</div>

@if($brands->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
        <div class="empty-title">No brands registered</div>
        <div class="empty-desc">Create manufacturer or supplier brands to attribute to your catalog items.</div>
        @can('brands.create', 'admin')
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">Create First Brand</a>
        @endcan
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Brand Name</th>
                    <th>Slug</th>
                    <th>Website</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($brands as $brand)
                    <tr>
                        <td style="width: 60px;">
                            @if($brand->logo_path)
                                <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="{{ $brand->name }}" style="width: 42px; height: 42px; object-fit: contain; background: #fff; border-radius: 4px; padding: 2px;">
                            @else
                                <div style="width: 42px; height: 42px; background: #334155; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #94a3b8; font-weight: 700;">
                                    {{ strtoupper(substr($brand->name, 0, 2)) }}
                                </div>
                            @endif
                        </td>
                        <td style="font-weight: 600; color: var(--text-main);">
                            {{ $brand->name }}
                        </td>
                        <td style="color: var(--text-muted); font-family: monospace; font-size: 0.8125rem;">
                            {{ $brand->slug }}
                        </td>
                        <td>
                            @if($brand->website)
                                <a href="{{ $brand->website }}" target="_blank" rel="noopener noreferrer" style="color: var(--primary-light); text-decoration: none; font-size: 0.8125rem;">
                                    {{ parse_url($brand->website, PHP_URL_HOST) ?? $brand->website }}
                                </a>
                            @else
                                <span style="color: var(--text-dim); font-size: 0.75rem;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $brand->products_count }}</span>
                        </td>
                        <td>
                            @can('brands.update', 'admin')
                                <form method="POST" action="{{ route('admin.brands.toggle-status', $brand) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="background: none; border: none; cursor: pointer;">
                                        @if($brand->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                @if($brand->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            @endcan
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            @can('brands.update', 'admin')
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @endcan

                            @can('brands.delete', 'admin')
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="openConfirmModal('{{ route('admin.brands.destroy', $brand) }}', 'Are you sure you want to delete brand \'{{ addslashes($brand->name) }}\'? This cannot be undone.', 'Delete Brand')">
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
        {{ $brands->links() }}
    </div>
@endif
@endsection
