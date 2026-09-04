@extends('layouts.admin')

@section('title', 'Edit Brand - ' . $brand->name)
@section('header_title', 'Edit Brand')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.brands.index') }}">Brands</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Edit ({{ $brand->name }})</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Brand: {{ $brand->name }}</h1>
        <p class="page-subtitle">Update brand credentials, logo, or SEO metadata.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.brands.update', $brand) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Brand Information</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Brand Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $brand->name) }}" required autofocus>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" id="slug" name="slug" class="form-input" value="{{ old('slug', $brand->slug) }}">
                @error('slug') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="website" class="form-label">Website</label>
                <input type="url" id="website" name="website" class="form-input" value="{{ old('website', $brand->website) }}">
                @error('website') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="logo" class="form-label">Replace Logo (Optional, Max 2MB)</label>
                <input type="file" id="logo" name="logo" class="form-input" accept="image/jpeg,image/png,image/webp,image/avif">
                <div class="form-hint">Leave blank to keep existing logo.</div>
                @error('logo') <div class="form-error">{{ $message }}</div> @enderror

                @if($brand->logo_path)
                    <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.75rem; color: var(--text-dim);">Current Logo:</span>
                        <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="{{ $brand->name }}" style="height: 36px; object-fit: contain; background: #fff; border-radius: 4px; padding: 2px;">
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-textarea" rows="4">{{ old('description', $brand->description) }}</textarea>
            @error('description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', (string)$brand->is_active) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Active Brand</span>
            </label>
        </div>
    </div>

    <!-- SEO Metadata Component -->
    <x-admin.seo-form :seo="$brand->seoMetadata" />

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Brand</button>
    </div>
</form>
@endsection
