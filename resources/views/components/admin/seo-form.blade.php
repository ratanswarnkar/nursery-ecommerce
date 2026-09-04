@props(['seo' => null])

<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Search Engine Optimization (SEO)</h3>
        <span class="badge badge-neutral">Polymorphic Metadata</span>
    </div>

    <div class="form-group">
        <label for="meta_title" class="form-label">Meta Title</label>
        <input type="text" id="meta_title" name="meta_title" class="form-input"
            value="{{ old('meta_title', $seo?->meta_title) }}" placeholder="Custom page title for search engines">
        <div class="form-hint">Recommended length: 50-60 characters</div>
        @error('meta_title') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="meta_description" class="form-label">Meta Description</label>
        <textarea id="meta_description" name="meta_description" class="form-textarea" rows="3"
            placeholder="Compelling summary snippet for search results">{{ old('meta_description', $seo?->meta_description) }}</textarea>
        <div class="form-hint">Recommended length: 150-160 characters</div>
        @error('meta_description') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="meta_keywords" class="form-label">Meta Keywords</label>
            <input type="text" id="meta_keywords" name="meta_keywords" class="form-input"
                value="{{ old('meta_keywords', $seo?->meta_keywords) }}" placeholder="plants, indoor, nursery, garden">
            @error('meta_keywords') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="canonical_url" class="form-label">Canonical URL</label>
            <input type="url" id="canonical_url" name="canonical_url" class="form-input"
                value="{{ old('canonical_url', $seo?->canonical_url) }}" placeholder="https://example.com/canonical-url">
            @error('canonical_url') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
