@extends('layouts.admin')

@section('title', 'Add Brand')
@section('header_title', 'Create Brand')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.brands.index') }}">Brands</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create New Brand</h1>
        <p class="page-subtitle">Register a manufacturer, nursery propagator, or supplier brand.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Brand Information</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Brand Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus placeholder="e.g., GreenThumb Nursery">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="slug" class="form-label">Slug (Optional)</label>
                <input type="text" id="slug" name="slug" class="form-input" value="{{ old('slug') }}" placeholder="greenthumb-nursery (auto-generated if empty)">
                @error('slug') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="website" class="form-label">Official Website</label>
                <input type="url" id="website" name="website" class="form-input" value="{{ old('website') }}" placeholder="https://example.com">
                @error('website') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="logo" class="form-label">Brand Logo (Max 2MB)</label>
                <input type="file" id="logo" name="logo" class="form-input" accept="image/jpeg,image/png,image/webp,image/avif">
                <div class="form-hint">Supported formats: JPEG, PNG, WebP, AVIF. SVG is not allowed.</div>
                @error('logo') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Brand Description</label>
            <textarea id="description" name="description" class="form-textarea" rows="4" placeholder="About the brand, heritage, or specialty...">{{ old('description') }}</textarea>
            @error('description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Active Brand</span>
            </label>
        </div>
    </div>

    <!-- SEO Metadata Component -->
    <x-admin.seo-form />

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Brand</button>
    </div>
</form>
@endsection
