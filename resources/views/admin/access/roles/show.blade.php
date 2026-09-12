@extends('layouts.admin')

@section('title', 'Role: ' . $role->name)
@section('header_title', 'Role Details')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.roles.index') }}" style="color: inherit; text-decoration: none;">Roles & Permissions</a>
    <span class="breadcrumbs-sep">/</span>
    <span>{{ $role->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $role->name }}</h1>
        <p class="page-subtitle">Guard: <code>{{ $role->guard_name }}</code> • Assigned Permissions: <strong>{{ $role->permissions->count() }}</strong></p>
    </div>
    <div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            ← Back to Roles
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Assigned Admins Card -->
    <div class="card" style="padding: 1.5rem;">
        <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">Assigned Administrators ({{ $admins->count() }})</h2>
        <p style="font-size: 0.8125rem; color: #64748b; margin-bottom: 1rem;">Users possessing the {{ $role->name }} role.</p>

        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            @forelse($admins as $admin)
                <div style="padding: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.375rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem; color: #0f172a;">{{ $admin->name }}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">{{ $admin->email }}</div>
                    </div>
                    @can('users.update', 'admin')
                        <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            Edit
                        </a>
                    @endcan
                </div>
            @empty
                <p style="font-size: 0.8125rem; color: #94a3b8;">No administrators currently assigned to this role.</p>
            @endforelse
        </div>
    </div>

    <!-- Permissions Breakdown Card -->
    <div class="card" style="padding: 1.5rem;">
        <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">Permission Access Matrix</h2>
        <p style="font-size: 0.8125rem; color: #64748b; margin-bottom: 1.5rem;">Green badges indicate active capabilities granted by this role.</p>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @foreach($permissionGroups as $group => $perms)
                <div style="border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem; background: #ffffff;">
                    <div style="font-weight: 700; font-size: 0.875rem; text-transform: uppercase; color: #334155; margin-bottom: 0.75rem;">
                        {{ $group }}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        @foreach($perms as $perm)
                            @php
                                $hasPermission = isset($rolePermissionNames[$perm->name]);
                            @endphp
                            <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-family: monospace; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; {{ $hasPermission ? 'background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-weight: 600;' : 'background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0;' }}">
                                @if($hasPermission)
                                    <svg style="width: 0.75rem; height: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                @else
                                    <svg style="width: 0.75rem; height: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                @endif
                                {{ $perm->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
