@extends('layouts.admin')

@section('title', 'Categories')
@section('header_title', 'Category Taxonomy')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Categories</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Categories & Taxonomy</h1>
        <p class="page-subtitle">Organize your plants, seeds, planters, and accessories into hierarchical categories.</p>
    </div>
    <div>
        @can('categories.create', 'admin')
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Category</span>
            </a>
        @endcan
    </div>
</div>

@if($categories->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
        <div class="empty-title">No categories found</div>
        <div class="empty-desc">Create your primary catalog departments and subcategories to structure your products.</div>
        @can('categories.create', 'admin')
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Create First Category</a>
        @endcan
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th>Parent Category</th>
                    <th>Products</th>
                    <th>Subcategories</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-main);">
                            <a href="{{ route('admin.categories.show', $category) }}" style="color: var(--primary-light); text-decoration: none;">
                                {{ $category->name }}
                            </a>
                        </td>
                        <td style="color: var(--text-muted); font-family: monospace; font-size: 0.8125rem;">
                            {{ $category->slug }}
                        </td>
                        <td>
                            @if($category->parent)
                                <span class="badge badge-neutral">{{ $category->parent->name }}</span>
                            @else
                                <span style="color: var(--text-dim); font-size: 0.75rem;">Root Department</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $category->products_count }}</span>
                        </td>
                        <td>
                            <span class="badge badge-neutral">{{ $category->children_count }}</span>
                        </td>
                        <td>
                            @can('categories.update', 'admin')
                                <form method="POST" action="{{ route('admin.categories.toggle-status', $category) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="background: none; border: none; cursor: pointer;">
                                        @if($category->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                @if($category->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            @endcan
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-secondary btn-sm" title="View Details">View</a>

                            @can('categories.update', 'admin')
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-secondary btn-sm" title="Edit Category">Edit</a>
                            @endcan

                            @can('categories.delete', 'admin')
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="openConfirmModal('{{ route('admin.categories.destroy', $category) }}', 'Are you sure you want to delete category \'{{ addslashes($category->name) }}\'? This cannot be undone.', 'Delete Category')">
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
        {{ $categories->links() }}
    </div>
@endif
@endsection
