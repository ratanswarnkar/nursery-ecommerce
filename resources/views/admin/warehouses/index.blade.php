@extends('layouts.admin')

@section('title', 'Warehouses')
@section('header_title', 'Warehouse Management')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Warehouses</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Warehouses & Storage Facilities</h1>
        <p class="page-subtitle">Manage physical nursery locations, greenhouses, and fulfillment centers.</p>
    </div>
    <div>
        @can('warehouses.create', 'admin')
            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Warehouse</span>
            </a>
        @endcan
    </div>
</div>

@if($warehouses->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
        <div class="empty-title">No warehouses registered</div>
        <div class="empty-desc">Create your primary warehouse or fulfillment center to begin tracking variant stock.</div>
        @can('warehouses.create', 'admin')
            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">Create First Warehouse</a>
        @endcan
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Warehouse Name</th>
                    <th>Location</th>
                    <th>Inventory Records</th>
                    <th>Default</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $warehouse)
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: var(--primary-light);">
                            {{ $warehouse->code }}
                        </td>
                        <td style="font-weight: 600; color: var(--text-main);">
                            {{ $warehouse->name }}
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.8125rem;">
                            @if($warehouse->city || $warehouse->state)
                                {{ implode(', ', array_filter([$warehouse->city, $warehouse->state, $warehouse->country])) }}
                            @else
                                <span style="color: var(--text-dim);">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $warehouse->inventories_count }} items</span>
                        </td>
                        <td>
                            @if($warehouse->is_default)
                                <span class="badge badge-success" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                                    ★ Default
                                </span>
                            @else
                                <span style="color: var(--text-dim); font-size: 0.75rem;">—</span>
                            @endif
                        </td>
                        <td>
                            @can('warehouses.update', 'admin')
                                <form method="POST" action="{{ route('admin.warehouses.toggle-status', $warehouse) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" style="background: none; border: none; cursor: pointer;">
                                        @if($warehouse->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                @if($warehouse->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            @endcan
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            @can('warehouses.update', 'admin')
                                <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @endcan

                            @can('warehouses.delete', 'admin')
                                @if(! $warehouse->is_default && $warehouse->inventories_count === 0)
                                    <button type="button" class="btn btn-danger btn-sm"
                                        onclick="openConfirmModal('{{ route('admin.warehouses.destroy', $warehouse) }}', 'Are you sure you want to delete warehouse \'{{ addslashes($warehouse->name) }}\'? This cannot be undone.', 'Delete Warehouse')">
                                        Delete
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $warehouses->links() }}
    </div>
@endif
@endsection
