@extends('layouts.admin')

@section('title', 'Images - ' . $product->name)
@section('header_title', 'Product Media Gallery')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.index') }}">Products</a>
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Images</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Media Gallery: {{ $product->name }}</h1>
        <p class="page-subtitle">Upload plant photography, set primary thumbnail, and assign variant-specific shots.</p>
    </div>
    <div>
        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary">Back to Product</a>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 340px 1fr; align-items: start;">
    <!-- Upload Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Upload Media</h2>
        </div>

        @can('products.update', 'admin')
            <form method="POST" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="images" class="form-label required">Select Image(s)</label>
                    <input type="file" id="images" name="images[]" multiple required class="form-input"
                        accept="image/jpeg,image/png,image/webp,image/avif">
                    <div class="form-hint">Max 5MB each. Supported: JPEG, PNG, WebP, AVIF. SVG disallowed.</div>
                    @error('images') <div class="form-error">{{ $message }}</div> @enderror
                    @error('images.*') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="product_variant_id" class="form-label">Variant Specific (Optional)</label>
                    <select id="product_variant_id" name="product_variant_id" class="form-select">
                        <option value="">— General Product Image —</option>
                        @foreach($variants as $variant)
                            <option value="{{ $variant->id }}">
                                Variant: {{ $variant->sku }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">Shown when customer selects this variant.</div>
                    @error('product_variant_id') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="alt_text" class="form-label">Alt Text (Accessibility / SEO)</label>
                    <input type="text" id="alt_text" name="alt_text" class="form-input" value="{{ old('alt_text') }}" placeholder="{{ $product->name }}">
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_primary" value="1">
                        <span style="font-size: 0.875rem; font-weight: 500;">Set as Primary Catalog Image</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                    Upload Images
                </button>
            </form>
        @else
            <p style="color: var(--text-dim); font-size: 0.875rem;">You do not have permission to upload images.</p>
        @endcan
    </div>

    <!-- Gallery Grid -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Gallery ({{ $images->count() }})</h2>
            <span class="badge badge-neutral">Exactly 1 primary image active</span>
        </div>

        @if($images->isEmpty())
            <div class="empty-state">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <div class="empty-title">No images uploaded</div>
                <div class="empty-desc">Upload product shots using the upload box on the left. The first uploaded image will automatically become the primary catalog photo.</div>
            </div>
        @else
            <div class="image-grid">
                @foreach($images as $img)
                    <div class="image-card">
                        <img src="{{ $img->url }}" alt="{{ $img->alt_text }}" class="image-thumb">

                        @if($img->is_primary)
                            <span class="badge badge-primary primary-tag">★ Primary</span>
                        @endif

                        <div style="padding: 0.5rem 0.6rem; font-size: 0.75rem; color: var(--text-dim);">
                            @if($img->variant)
                                <span class="badge badge-neutral" style="font-size: 0.65rem;">Variant: {{ $img->variant->sku }}</span>
                            @else
                                <span>General Product</span>
                            @endif
                        </div>

                        <div class="image-card-actions">
                            @if(! $img->is_primary)
                                @can('products.update', 'admin')
                                    <form method="POST" action="{{ route('admin.products.images.set-primary', [$product, $img]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Set as primary thumbnail">
                                            Make Primary
                                        </button>
                                    </form>
                                @endcan
                            @else
                                <span style="font-size: 0.75rem; color: #34d399; font-weight: 600;">Main Photo</span>
                            @endif

                            @can('products.delete', 'admin')
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="openConfirmModal('{{ route('admin.products.images.destroy', [$product, $img]) }}', 'Are you sure you want to delete this image? If this was the primary image, another image will be promoted automatically.', 'Delete Image')">
                                    Delete
                                </button>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
