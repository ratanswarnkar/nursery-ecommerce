@extends('layouts.admin')

@section('title', 'Create Tender Contract')
@section('header_title', 'New Tender')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.tenders.index') }}">Tenders</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Register New Tender Contract</h1>
        <p class="page-subtitle">Add official municipal, government or corporate landscape tender details.</p>
    </div>
    <div>
        <a href="{{ route('admin.tenders.index') }}" class="btn btn-secondary">&larr; Back to Tenders</a>
    </div>
</div>

<div class="card" style="max-width: 900px;">
    <form method="POST" action="{{ route('admin.tenders.store') }}">
        @csrf

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">Tender Reference Number</label>
                <input type="text" name="tender_number" class="form-input" value="{{ old('tender_number') }}" placeholder="e.g. CPWD-2026-DEL-042" required>
                @error('tender_number') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label required">Tender Title / Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="e.g. Central Vista Landscape Development" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">Department / Client Name</label>
                <input type="text" name="department_name" class="form-input" value="{{ old('department_name') }}" placeholder="e.g. CPWD Horticulture Division" required>
                @error('department_name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Project Name</label>
                <input type="text" name="project_name" class="form-input" value="{{ old('project_name') }}" placeholder="e.g. Parliament Complex Phase 2">
                @error('project_name') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Scope & Description</label>
            <textarea name="description" class="form-textarea" rows="3" placeholder="Enter tender scope of work, location, contract terms...">{{ old('description') }}</textarea>
            @error('description') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="form-group">
                <label class="form-label required">Original SOQ Value (₹)</label>
                <input type="number" step="0.01" name="original_soq_value" class="form-input" value="{{ old('original_soq_value') }}" placeholder="0.00" required>
                @error('original_soq_value') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label required">Awarded Value (₹)</label>
                <input type="number" step="0.01" name="awarded_value" class="form-input" value="{{ old('awarded_value') }}" placeholder="0.00" required>
                @error('awarded_value') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Below/Above %</label>
                <input type="number" step="0.0001" name="below_above_percentage" class="form-input" value="{{ old('below_above_percentage') }}" placeholder="-12.50">
                <span class="form-hint">Negative for below, positive for above</span>
                @error('below_above_percentage') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">Pricing Mode</label>
                <select name="pricing_mode" class="form-select" required>
                    @foreach($pricingModes as $pm)
                        <option value="{{ $pm->value }}" {{ old('pricing_mode') === $pm->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $pm->value)) }}
                        </option>
                    @endforeach
                </select>
                @error('pricing_mode') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label required">Initial Status</label>
                <select name="status" class="form-select" required>
                    @foreach($statuses as $st)
                        <option value="{{ $st->value }}" {{ old('status', 'draft') === $st->value ? 'selected' : '' }}>
                            {{ ucfirst($st->value) }}
                        </option>
                    @endforeach
                </select>
                @error('status') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Contract Start Date</label>
                <input type="date" name="start_date" class="form-input" value="{{ old('start_date') }}">
                @error('start_date') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Contract End Date</label>
                <input type="date" name="end_date" class="form-input" value="{{ old('end_date') }}">
                @error('end_date') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-group" style="margin-top: 0.5rem;">
            <label class="form-check" style="cursor: pointer;">
                <input type="checkbox" name="special_billing_enabled" value="1" {{ old('special_billing_enabled') ? 'checked' : '' }}>
                <span>Enable Special Billing (Allows custom department billing rules independent of retail catalogue)</span>
            </label>
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
            <a href="{{ route('admin.tenders.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Tender</button>
        </div>
    </form>
</div>
@endsection
