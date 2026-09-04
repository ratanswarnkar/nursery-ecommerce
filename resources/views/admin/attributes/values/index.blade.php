@extends('layouts.admin')

@section('title', 'Manage Values - ' . $attribute->name)
@section('header_title', 'Attribute Values')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.attributes.index') }}">Attributes</a>
    <span class="breadcrumbs-sep">/</span>
    <span>{{ $attribute->name }} (Values)</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Values for: {{ $attribute->name }}</h1>
        <p class="page-subtitle">Predefined options and variant labels for the <code style="color: var(--primary-light);">{{ $attribute->code }}</code> attribute.</p>
    </div>
    <div>
        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">Back to Attributes</a>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 1fr 340px; align-items: start;">
    <!-- Existing Values Table -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Configured Options ({{ $values->count() }})</h2>
        </div>

        @if($values->isEmpty())
            <div class="empty-state" style="padding: 2rem;">
                <div class="empty-title">No options added yet</div>
                <div class="empty-desc">Use the form on the right to add values (e.g., "Terracotta", "White", "Small", "Large").</div>
            </div>
        @else
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Label (Display)</th>
                            <th>Raw Value (Code)</th>
                            <th>Sort Order</th>
                            <th>Variant Usage</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($values as $val)
                            <tr>
                                <td style="font-weight: 600; color: var(--text-main);">
                                    {{ $val->label }}
                                </td>
                                <td style="font-family: monospace; color: var(--text-muted); font-size: 0.8125rem;">
                                    {{ $val->value }}
                                </td>
                                <td>
                                    {{ $val->sort_order }}
                                </td>
                                <td>
                                    <span class="badge {{ $val->variants_count > 0 ? 'badge-primary' : 'badge-neutral' }}">
                                        {{ $val->variants_count }} variants
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    @can('attributes.delete', 'admin')
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="openConfirmModal('{{ route('admin.attributes.values.destroy', [$attribute, $val]) }}', 'Are you sure you want to delete value \'{{ addslashes($val->label) }}\'?', 'Delete Value')">
                                            Delete
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Add Option Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Add New Option</h2>
        </div>

        @can('attributes.create', 'admin')
            <form method="POST" action="{{ route('admin.attributes.values.store', $attribute) }}">
                @csrf

                <div class="form-group">
                    <label for="label" class="form-label required">Display Label</label>
                    <input type="text" id="label" name="label" class="form-input" value="{{ old('label') }}" required placeholder="e.g., Terracotta Red">
                    <div class="form-hint">Shown to customers on storefront.</div>
                    @error('label') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="value" class="form-label required">Internal Value / Slug</label>
                    <input type="text" id="value" name="value" class="form-input" value="{{ old('value') }}" required placeholder="e.g., terracotta">
                    <div class="form-hint">Unique identifier for this attribute.</div>
                    @error('value') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-input" value="{{ old('sort_order', 0) }}" min="0">
                    @error('sort_order') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                    Add Option Value
                </button>
            </form>
        @else
            <p style="color: var(--text-dim); font-size: 0.875rem;">You do not have permission to add new attribute values.</p>
        @endcan
    </div>
</div>
@endsection
