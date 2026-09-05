@extends('layouts.admin')

@section('title', 'Edit Warehouse: ' . $warehouse->name)
@section('header_title', 'Edit Warehouse')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.warehouses.index') }}">Warehouses</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Edit: {{ $warehouse->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Warehouse</h1>
        <p class="page-subtitle">Update facility address, operating status, or default assignment.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.warehouses.update', $warehouse) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Warehouse Details</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Warehouse Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $warehouse->name) }}" required autofocus>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="code" class="form-label required">Warehouse Code (Unique)</label>
                <input type="text" id="code" name="code" class="form-input" value="{{ old('code', $warehouse->code) }}" required>
                @error('code') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="address_line_1" class="form-label">Street Address</label>
            <input type="text" id="address_line_1" name="address_line_1" class="form-input" value="{{ old('address_line_1', $warehouse->address_line_1) }}">
            @error('address_line_1') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="city" class="form-label">City</label>
                <input type="text" id="city" name="city" class="form-input" value="{{ old('city', $warehouse->city) }}">
                @error('city') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="state" class="form-label">State / Province</label>
                <input type="text" id="state" name="state" class="form-input" value="{{ old('state', $warehouse->state) }}">
                @error('state') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="postal_code" class="form-label">Postal / PIN Code</label>
                <input type="text" id="postal_code" name="postal_code" class="form-input" value="{{ old('postal_code', $warehouse->postal_code) }}">
                @error('postal_code') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="country" class="form-label">Country</label>
                <input type="text" id="country" name="country" class="form-input" value="{{ old('country', $warehouse->country) }}">
                @error('country') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 1rem;">
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                    <span style="font-size: 0.875rem; font-weight: 500;">Active Facility</span>
                </label>
                <div class="form-hint">Deactivating the default warehouse will safely reassign default to another active warehouse.</div>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $warehouse->is_default) ? 'checked' : '' }}>
                    <span style="font-size: 0.875rem; font-weight: 500;">Default Warehouse</span>
                </label>
                <div class="form-hint">Exactly one active default warehouse is always maintained.</div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.warehouses.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Warehouse</button>
    </div>
</form>
@endsection
