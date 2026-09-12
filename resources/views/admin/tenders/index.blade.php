@extends('layouts.admin')

@section('title', 'Tender Management')
@section('header_title', 'Tenders & Contracts')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Tenders</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Institutional & Government Tenders</h1>
        <p class="page-subtitle">Manage municipal, CPWD, corporate contracts, SOQ items, requirement calls, and RA bills.</p>
    </div>
    <div>
        @can('tenders.create', 'admin')
            <a href="{{ route('admin.tenders.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>New Tender</span>
            </a>
        @endcan
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('admin.tenders.index') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 2; min-width: 220px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Tender / Project</label>
            <input type="text" name="q" class="form-input" placeholder="Tender number, name, department..." value="{{ request('q') }}">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <label class="form-label" style="font-size: 0.75rem;">Status</label>
            <select name="status" class="form-select">
                <option value="">— All Statuses —</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                        {{ ucfirst($st->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
            <label class="form-label" style="font-size: 0.75rem;">Pricing Mode</label>
            <select name="pricing_mode" class="form-select">
                <option value="">— All Modes —</option>
                @foreach($pricingModes as $pm)
                    <option value="{{ $pm->value }}" {{ request('pricing_mode') === $pm->value ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $pm->value)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Filter</button>
            @if(request()->anyFilled(['q', 'status', 'pricing_mode']))
                <a href="{{ route('admin.tenders.index') }}" class="btn btn-secondary" style="padding: 0.55rem 1rem;">Reset</a>
            @endif
        </div>
    </form>
</div>

<!-- Tenders Table -->
<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Tender #</th>
                    <th>Tender Name / Department</th>
                    <th>Awarded Value</th>
                    <th>SOQ Value</th>
                    <th>Items</th>
                    <th>Bills</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenders as $tender)
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: var(--primary-light);">
                            <a href="{{ route('admin.tenders.show', $tender) }}" style="color: inherit; text-decoration: none;">
                                #{{ $tender->tender_number }}
                            </a>
                        </td>
                        <td>
                            <div style="font-weight: 600;">
                                <a href="{{ route('admin.tenders.show', $tender) }}" style="color: var(--text-main); text-decoration: none;">
                                    {{ $tender->name }}
                                </a>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                {{ $tender->department_name }}
                                @if($tender->project_name)
                                    &bull; <span style="color: var(--text-dim);">{{ $tender->project_name }}</span>
                                @endif
                            </div>
                        </td>
                        <td style="font-weight: 700; color: #10b981;">
                            ₹{{ number_format((float)$tender->awarded_value, 2) }}
                        </td>
                        <td style="font-size: 0.8125rem; color: var(--text-muted);">
                            ₹{{ number_format((float)$tender->original_soq_value, 2) }}
                        </td>
                        <td>
                            <span class="badge badge-neutral">{{ $tender->items_count }} items</span>
                        </td>
                        <td>
                            <span class="badge badge-neutral">{{ $tender->bills_count }} bills</span>
                        </td>
                        <td>
                            @php
                                $badgeClass = match($tender->status->value) {
                                    'active' => 'badge-success',
                                    'completed' => 'badge-primary',
                                    'draft' => 'badge-warning',
                                    'cancelled', 'archived' => 'badge-danger',
                                    default => 'badge-neutral',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ ucfirst($tender->status->value) }}
                            </span>
                            @if($tender->special_billing_enabled)
                                <span class="badge badge-primary" style="font-size: 0.65rem; margin-top: 0.2rem;" title="Special Billing Enabled">Special</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.tenders.show', $tender) }}" class="btn btn-secondary btn-sm">Overview</a>
                            @can('tenders.update', 'admin')
                                <a href="{{ route('admin.tenders.edit', $tender) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-dim);">
                            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <div class="empty-title">No tender contracts found</div>
                            <div class="empty-desc">Create your first institutional or government contract to manage SOQ schedules and supply requirements.</div>
                            @can('tenders.create', 'admin')
                                <a href="{{ route('admin.tenders.create') }}" class="btn btn-primary btn-sm">Add First Tender</a>
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenders->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-color);">
            {{ $tenders->links() }}
        </div>
    @endif
</div>
@endsection
