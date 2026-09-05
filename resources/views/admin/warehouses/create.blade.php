@extends('layouts.admin')

@section('title', 'Add Warehouse')
@section('header_title', 'Create Warehouse')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.warehouses.index') }}">Warehouses</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create New Warehouse</h1>
        <p class="page-subtitle">Register a physical facility, greenhouse, or staging depot.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.warehouses.store') }}">
    @csrf

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Warehouse Details</h2>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label required">Warehouse Name</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus placeholder="e.g., Central Greenhouse Depot">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="code" class="form-label required">Warehouse Code (Unique)</label>
                <input type="text" id="code" name="code" class="form-input" value="{{ old('code') }}" required placeholder="e.g., WH-MAIN or GH-01">
                @error('code') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="address_line_1" class="form-label">Street Address</label>
            <input type="text" id="address_line_1" name="address_line_1" class="form-input" value="{{ old('address_line_1') }}" placeholder="123 Nursery Lane">
            @error('address_line_1') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="city" class="form-label">City</label>
                <input type="text" id="city" name="city" class="form-input" value="{{ old('city') }}" placeholder="e.g., Pune">
                @error('city') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="state" class="form-label">State / Province</label>
                <input type="text" id="state" name="state" class="form-input" value="{{ old('state') }}" placeholder="e.g., Maharashtra">
                @error('state') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="postal_code" class="form-label">Postal / PIN Code</label>
                <input type="text" id="postal_code" name="postal_code" class="form-input" value="{{ old('postal_code') }}" placeholder="e.g., 411001">
                @error('postal_code') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="country" class="form-label">Country</label>
                <input type="text" id="country" name="country" class="form-input" value="{{ old('country', 'India') }}" placeholder="India">
                @error('country') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 1rem;">
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <span style="font-size: 0.875rem; font-weight: 500;">Active Facility</span>
                </label>
                <div class="form-hint">Only active warehouses fulfill customer orders.</div>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                    <span style="font-size: 0.875rem; font-weight: 500;">Set as Default Warehouse</span>
                </label>
                <div class="form-hint">Marking as default will demote any previous default warehouse.</div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
        <a href="{{ route('admin.warehouses.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Warehouse</button>
    </div>
</form>
@endsection
