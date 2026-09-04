@extends('layouts.admin')

@section('title', 'Add Category')
@section('header_title', 'Create Category')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.categories.index') }}">Categories</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create New Category</h1>
        <p class="page-subtitle">Add a top-level department or subcategory to your catalog tree.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.categories.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Category Details</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Category Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus placeholder="e.g., Flowering Plants">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="slug" class="form-label">Custom Slug (Optional)</label>
                <input type="text" id="slug" name="slug" class="form-input" value="{{ old('slug') }}" placeholder="flowering-plants (auto-generated if left blank)">
                @error('slug') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select id="parent_id" name="parent_id" class="form-select">
                    <option value="">— None (Top-Level Category) —</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent['id'] }}" {{ old('parent_id') == $parent['id'] ? 'selected' : '' }}>
                            {{ $parent['name'] }}
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">Select a parent to make this a subcategory.</div>
                @error('parent_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" class="form-input" value="{{ old('sort_order', 0) }}" min="0">
                @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-textarea" rows="4" placeholder="Description of this category for administration and SEO...">{{ old('description') }}</textarea>
            @error('description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Active & Visible in Catalog</span>
            </label>
        </div>
    </div>

    <!-- SEO Metadata Component -->
    <x-admin.seo-form />

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Category</button>
    </div>
</form>
@endsection
