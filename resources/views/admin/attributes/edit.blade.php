@extends('layouts.admin')

@section('title', 'Edit Attribute - ' . $attribute->name)
@section('header_title', 'Edit Attribute')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.attributes.index') }}">Attributes</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Edit ({{ $attribute->name }})</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Attribute: {{ $attribute->name }}</h1>
        <p class="page-subtitle">Update specification labels, type, or sorting preferences.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.attributes.update', $attribute) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Attribute Specification</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Attribute Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $attribute->name) }}" required autofocus>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="code" class="form-label required">Unique Code</label>
                <input type="text" id="code" name="code" class="form-input" value="{{ old('code', $attribute->code) }}" required>
                @error('code') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="type" class="form-label required">Attribute Type</label>
                <select id="type" name="type" class="form-select" required>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ old('type', $attribute->type->value) == $type->value ? 'selected' : '' }}>
                            {{ ucfirst($type->value) }}
                        </option>
                    @endforeach
                </select>
                @error('type') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="sort_order" class="form-label">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" class="form-input" value="{{ old('sort_order', $attribute->sort_order) }}" min="0">
                @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
            <label class="form-check">
                <input type="checkbox" name="is_filterable" value="1" {{ old('is_filterable', (string)$attribute->is_filterable) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Enable in Storefront Filters</span>
            </label>

            <label class="form-check">
                <input type="checkbox" name="is_required" value="1" {{ old('is_required', (string)$attribute->is_required) == '1' ? 'checked' : '' }}>
                <span style="font-size: 0.875rem; font-weight: 500;">Required for Products</span>
            </label>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Attribute</button>
    </div>
</form>
@endsection
