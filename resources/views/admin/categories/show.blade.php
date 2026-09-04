@extends('layouts.admin')

@section('title', 'Category Details - ' . $category->name)
@section('header_title', 'Category Details')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.categories.index') }}">Categories</a>
    <span class="breadcrumbs-sep">/</span>
    <span>{{ $category->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $category->name }}</h1>
        <p class="page-subtitle">Department details, hierarchy overview, and associated products.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        @can('categories.update', 'admin')
            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-secondary">Edit Category</a>
        @endcan
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.create') }}?category_id={{ $category->id }}" class="btn btn-primary">Add Product Here</a>
        @endcan
    </div>
</div>

<div class="form-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Overview</h2>
            @if($category->is_active)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-danger">Inactive</span>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem; font-size: 0.875rem;">
            <div style="color: var(--text-dim); font-weight: 600;">Slug:</div>
            <div style="font-family: monospace; color: var(--primary-light);">{{ $category->slug }}</div>

            <div style="color: var(--text-dim); font-weight: 600;">Parent:</div>
            <div>
                @if($category->parent)
                    <a href="{{ route('admin.categories.show', $category->parent) }}" style="color: var(--primary-light); text-decoration: none;">
                        {{ $category->parent->name }}
                    </a>
                @else
                    <span style="color: var(--text-dim);">None (Root Department)</span>
                @endif
            </div>

            <div style="color: var(--text-dim); font-weight: 600;">Products Count:</div>
            <div><span class="badge badge-primary">{{ $category->products_count }} Products</span></div>

            <div style="color: var(--text-dim); font-weight: 600;">Sort Order:</div>
            <div>{{ $category->sort_order }}</div>

            <div style="color: var(--text-dim); font-weight: 600;">Created:</div>
            <div>{{ $category->created_at->format('M d, Y H:i') }}</div>
        </div>

        @if($category->description)
            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <div style="font-weight: 600; font-size: 0.8125rem; color: var(--text-dim); margin-bottom: 0.4rem;">Description</div>
                <div style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">{{ $category->description }}</div>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Direct Subcategories ({{ $category->children->count() }})</h2>
        </div>

        @if($category->children->isEmpty())
            <div class="empty-state" style="padding: 1.5rem;">
                <div class="empty-title" style="font-size: 0.95rem;">No subcategories</div>
                <div class="empty-desc" style="font-size: 0.8125rem;">This category does not have any nested child departments.</div>
            </div>
        @else
            <ul style="list-style: none;">
                @foreach($category->children as $child)
                    <li style="padding: 0.6rem 0; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <a href="{{ route('admin.categories.show', $child) }}" style="color: var(--text-main); font-weight: 500; text-decoration: none;">
                            {{ $child->name }}
                        </a>
                        @if($child->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
