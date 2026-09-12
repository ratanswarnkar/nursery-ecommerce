@extends('layouts.admin')

@section('title', 'Login Activity')
@section('header_title', 'Login Activity')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Security</span>
    <span class="breadcrumbs-sep">/</span>
    <span>Login Activity</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Administrator Login Activity</h1>
        <p class="page-subtitle">Historical records of successful and failed administrator authentication attempts.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.login-activity.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Administrator / IP</label>
            <input type="text" name="search" class="form-input" placeholder="Admin name, email, IP address..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 160px;">
            <label class="form-label" style="font-size: 0.75rem;">Attempt Outcome</label>
            <select name="status" class="form-select">
                <option value="">All Outcomes</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Successful</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Failed Attempt</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('admin.login-activity.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Activity Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Administrator</th>
                    <th>IP Address</th>
                    <th>Outcome</th>
                    <th>Failure Reason</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                    <tr>
                        <td style="font-size: 0.8125rem; color: #64748b; white-space: nowrap;">
                            {{ $act->login_at ? $act->login_at->format('d M Y, H:i:s') : $act->created_at->format('d M Y, H:i:s') }}
                        </td>
                        <td>
                            @if($act->admin)
                                <div style="font-weight: 600; font-size: 0.8125rem; color: #0f172a;">{{ $act->admin->name }}</div>
                                <div style="font-size: 0.6875rem; color: #64748b;">{{ $act->admin->email }}</div>
                            @else
                                <span style="font-size: 0.8125rem; color: #94a3b8;">Unknown User</span>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 0.8125rem; color: #334155;">
                            {{ $act->ip_address }}
                        </td>
                        <td>
                            @if($act->is_successful)
                                <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #15803d; text-transform: uppercase;">
                                    Success
                                </span>
                            @else
                                <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c; text-transform: uppercase;">
                                    Failed
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $act->failure_reason ?: '—' }}
                        </td>
                        <td style="font-size: 0.75rem; color: #64748b; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $act->user_agent }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No login activities recorded matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($activities->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $activities->links() }}
        </div>
    @endif
</div>
@endsection
