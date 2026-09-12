@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('header_title', 'Roles')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Access Control</span>
    <span class="breadcrumbs-sep">/</span>
    <span>Roles & Permissions</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Roles & Permissions Matrix</h1>
        <p class="page-subtitle">Inspect role-based access control (RBAC), assigned administrators, and system permission scopes.</p>
    </div>
</div>

<!-- Roles Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
    @foreach($roles as $role)
        <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <div>
                        <h2 style="font-size: 1.125rem; font-weight: 700; color: #0f172a; margin: 0;">{{ $role->name }}</h2>
                        <span style="font-size: 0.75rem; color: #64748b;">Guard: {{ $role->guard_name }}</span>
                    </div>
                    <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #e0f2fe; color: #0369a1;">
                        {{ $role->users_count }} Admins
                    </span>
                </div>
                <p style="font-size: 0.8125rem; color: #475569; margin-bottom: 1rem;">
                    Grants access to <strong>{{ $role->permissions_count }}</strong> granular permissions across the administrative system.
                </p>
            </div>
            <div style="border-top: 1px solid #f1f5f9; padding-top: 1rem; display: flex; justify-content: flex-end;">
                <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                    View Permissions & Admins →
                </a>
            </div>
        </div>
    @endforeach
</div>

<!-- Permission Catalog -->
<div class="card" style="padding: 1.5rem;">
    <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-bottom: 0.5rem;">System Permission Directory</h2>
    <p style="font-size: 0.8125rem; color: #64748b; margin-bottom: 1.5rem;">All registered permission keys mapped into logical functional modules.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
        @foreach($permissionGroups as $group => $perms)
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem;">
                <div style="font-weight: 700; font-size: 0.875rem; text-transform: uppercase; color: #0f172a; margin-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.375rem;">
                    {{ $group }} ({{ $perms->count() }})
                </div>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.375rem;">
                    @foreach($perms as $perm)
                        <li style="font-family: monospace; font-size: 0.75rem; color: #334155; background: #ffffff; padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e2e8f0;">
                            {{ $perm->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
@endsection
