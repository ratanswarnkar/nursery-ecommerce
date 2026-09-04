@extends('layouts.admin')

@section('title', 'Attributes')
@section('header_title', 'Product Attributes')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Attributes</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Product Attributes</h1>
        <p class="page-subtitle">Configure dynamic specification fields and variant dimensions (e.g., Pot Size, Plant Height, Color).</p>
    </div>
    <div>
        @can('attributes.create', 'admin')
            <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Attribute</span>
            </a>
        @endcan
    </div>
</div>

@if($attributes->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
        <div class="empty-title">No attributes configured</div>
        <div class="empty-desc">Create attributes like "Pot Size", "Color", or "Pack Size" to build product variations.</div>
        @can('attributes.create', 'admin')
            <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">Create First Attribute</a>
        @endcan
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Attribute Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Filterable</th>
                    <th>Required</th>
                    <th>Options / Values</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attributes as $attribute)
                    <tr>
                        <td style="font-weight: 600; color: var(--text-main);">
                            {{ $attribute->name }}
                        </td>
                        <td style="font-family: monospace; color: var(--primary-light); font-size: 0.8125rem;">
                            {{ $attribute->code }}
                        </td>
                        <td>
                            <span class="badge badge-neutral">{{ strtoupper($attribute->type->value) }}</span>
                        </td>
                        <td>
                            @if($attribute->is_filterable)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-neutral">No</span>
                            @endif
                        </td>
                        <td>
                            @if($attribute->is_required)
                                <span class="badge badge-warning">Required</span>
                            @else
                                <span class="badge badge-neutral">Optional</span>
                            @endif
                        </td>
                        <td>
                            @if(in_array($attribute->type->value, ['select', 'multiselect']))
                                <a href="{{ route('admin.attributes.values.index', $attribute) }}" class="btn btn-secondary btn-sm">
                                    Manage Values ({{ $attribute->values_count }})
                                </a>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-dim);">Custom Value (No Options)</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            @can('attributes.update', 'admin')
                                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @endcan

                            @can('attributes.delete', 'admin')
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="openConfirmModal('{{ route('admin.attributes.destroy', $attribute) }}', 'Are you sure you want to delete attribute \'{{ addslashes($attribute->name) }}\'? This will delete its values if unused.', 'Delete Attribute')">
                                    Delete
                                </button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $attributes->links() }}
    </div>
@endif
@endsection
