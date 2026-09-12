@extends('layouts.admin')

@section('title', 'Administrator Accounts')
@section('header_title', 'Administrators')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Access Control</span>
    <span class="breadcrumbs-sep">/</span>
    <span>Administrators</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Administrator Accounts</h1>
        <p class="page-subtitle">Manage internal administrative users, role assignments, two-factor authentication, and account access.</p>
    </div>
    @can('users.create', 'admin')
        <a href="{{ route('admin.admins.create') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Administrator
        </a>
    @endcan
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.admins.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Administrator</label>
            <input type="text" name="search" class="form-input" placeholder="Name or email address..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 160px;">
            <label class="form-label" style="font-size: 0.75rem;">Status</label>
            <select name="is_active" class="form-select">
                <option value="">All Statuses</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'is_active']))
                <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Admins Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Assigned Roles</th>
                    <th>2FA Enrolled</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td style="font-weight: 600; color: #0f172a;">
                            {{ $admin->name }}
                            @if(auth('admin')->id() === $admin->id)
                                <span style="font-size: 0.6875rem; background: #e0e7ff; color: #3730a3; padding: 0.125rem 0.375rem; border-radius: 9999px; font-weight: 600; margin-left: 0.25rem;">You</span>
                            @endif
                        </td>
                        <td style="color: #475569; font-size: 0.8125rem;">
                            {{ $admin->email }}
                        </td>
                        <td>
                            @foreach($admin->roles as $role)
                                <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; background: #e0f2fe; color: #0369a1; margin-right: 0.25rem;">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @if($admin->two_factor_confirmed_at)
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: #166534; font-weight: 600;">
                                    <svg style="width: 0.875rem; height: 0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Enrolled
                                </span>
                            @else
                                <span style="font-size: 0.75rem; color: #94a3b8;">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($admin->is_active)
                                <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #15803d; text-transform: uppercase;">
                                    Active
                                </span>
                            @else
                                <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c; text-transform: uppercase;">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $admin->created_at->format('d M Y') }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 0.375rem;">
                                @can('users.update', 'admin')
                                    <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                        Edit
                                    </a>
                                    @if(auth('admin')->id() !== $admin->id)
                                        <form method="POST" action="{{ route('admin.admins.toggle-status', $admin) }}" style="display: inline;" onsubmit="return confirm('Toggle active status for this administrator?');">
                                            @csrf
                                            <button type="submit" class="btn {{ $admin->is_active ? 'btn-danger' : 'btn-success' }}" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No administrators found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($admins->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $admins->links() }}
        </div>
    @endif
</div>
@endsection
