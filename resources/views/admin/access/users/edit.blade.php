@extends('layouts.admin')

@section('title', 'Edit Administrator: ' . $admin->name)
@section('header_title', 'Edit Administrator')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.admins.index') }}" style="color: inherit; text-decoration: none;">Administrators</a>
    <span class="breadcrumbs-sep">/</span>
    <span>{{ $admin->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Administrator</h1>
        <p class="page-subtitle">Update administrator account details and assigned roles.</p>
    </div>
</div>

<div class="card" style="max-width: 640px; padding: 1.5rem;">
    <form method="POST" action="{{ route('admin.admins.update', $admin) }}">
        @csrf
        @method('PUT')

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Full Name <span style="color: #ef4444;">*</span></label>
            <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $admin->name) }}" required>
            @error('name')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Email Address <span style="color: #ef4444;">*</span></label>
            <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email', $admin->email) }}" required>
            @error('email')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">New Password (Leave blank to keep unchanged)</label>
            <input type="password" name="password" class="form-input @error('password') is-invalid @enderror">
            @error('password')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-input">
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Assign Role(s) <span style="color: #ef4444;">*</span></label>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.25rem;">
                @php
                    $currentRoleNames = $admin->roles->pluck('name')->toArray();
                @endphp
                @foreach($roles as $role)
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #334155; cursor: pointer;">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" {{ in_array($role->name, old('roles', $currentRoleNames)) ? 'checked' : '' }}>
                        <span style="font-weight: 500;">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        @if(auth('admin')->id() !== $admin->id)
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $admin->is_active) ? 'checked' : '' }}>
                    <span>Account Active (Allowed to log in)</span>
                </label>
            </div>
        @else
            <input type="hidden" name="is_active" value="1">
        @endif

        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Update Administrator</button>
            <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
