@extends('layouts.admin')

@section('title', 'Add Attribute')
@section('header_title', 'Create Attribute')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.attributes.index') }}">Attributes</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create Product Attribute</h1>
        <p class="page-subtitle">Configure attribute type, unique code, and sorting precedence.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.attributes.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Attribute Specification</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Attribute Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus placeholder="e.g., Pot Diameter">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="code" class="form-label required">Unique Code</label>
                <input type="text" id="code" name="code" class="form-input" value="{{ old('code') }}" required placeholder="e.g., pot_diameter (lowercase, numbers, underscores)">
                <div class="form-hint">Used for internal catalog queries and variant matrix mapping.</div>
                @error('code') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="type" class="form-label required">Attribute Type</label>
                <select id="type" name="type" class="form-select" required>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ old('type') == $type->value ? 'selected' : '' }}>
                            {{ ucfirst($type->value) }} {{ in_array($type->value, ['select', 'multiselect']) ? '(Allows Predefined Options/Variants)' : '(Stored as Custom Attribute Value)' }}
                        </option>
                    @endforeach
                </select>
                @error('type') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="sort_order" class="form-label">Display Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" class="form-input" value="{{ old('sort_order', 0) }}" min="0">
                @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
            <label class="form-check">
                <input type="checkbox" name="is_filterable" value="1" {{ old('is_filterable', '1') == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Enable in Storefront Filters</span>
            </label>

            <label class="form-check">
                <input type="checkbox" name="is_required" value="1" {{ old('is_required') == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Required for Products</span>
            </label>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Attribute</button>
    </div>
</form>
@endsection
