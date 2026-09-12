@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('header_title', 'Audit Logs')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Security</span>
    <span class="breadcrumbs-sep">/</span>
    <span>Audit Logs</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">System Audit Logs</h1>
        <p class="page-subtitle">Chronological record of administrative events, model mutations, and security changes.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Action / Model / IP</label>
            <input type="text" name="search" class="form-input" placeholder="Action name, IP address, model type..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Action Type</label>
            <select name="action" class="form-select">
                <option value="">All Actions</option>
                @foreach($distinctActions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                        {{ $act }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'action']))
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Audit Logs Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Target Model</th>
                    <th>IP Address</th>
                    <th>Changes Snapshot</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="font-size: 0.8125rem; color: #64748b; white-space: nowrap;">
                            {{ $log->created_at->format('d M Y, H:i:s') }}
                        </td>
                        <td>
                            @if($log->admin)
                                <div style="font-weight: 600; font-size: 0.8125rem; color: #0f172a;">{{ $log->admin->name }}</div>
                                <div style="font-size: 0.6875rem; color: #64748b;">Admin #{{ $log->admin_id }}</div>
                            @elseif($log->customer)
                                <div style="font-weight: 600; font-size: 0.8125rem; color: #0f172a;">{{ $log->customer->name }}</div>
                                <div style="font-size: 0.6875rem; color: #64748b;">Customer #{{ $log->customer_id }}</div>
                            @else
                                <span style="font-size: 0.8125rem; color: #94a3b8;">System Process</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-family: monospace; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #334155; padding: 0.2rem 0.5rem; border-radius: 0.25rem;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            @if($log->auditable_type)
                                <span style="font-size: 0.8125rem; color: #0369a1; font-weight: 500;">
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                </span>
                            @else
                                <span style="color: #94a3b8; font-size: 0.8125rem;">—</span>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 0.75rem; color: #475569;">
                            {{ $log->ip_address ?: '127.0.0.1' }}
                        </td>
                        <td style="font-size: 0.75rem; max-width: 320px;">
                            @if($log->new_values)
                                <details style="cursor: pointer;">
                                    <summary style="color: #2563eb; font-weight: 500;">View JSON Diff</summary>
                                    <pre style="margin-top: 0.375rem; padding: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.25rem; font-size: 0.6875rem; overflow-x: auto; max-height: 150px;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @else
                                <span style="color: #94a3b8;">No payload recorded</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No audit log entries recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
