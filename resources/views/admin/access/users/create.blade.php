@extends('layouts.admin')

@section('title', 'Add Administrator')
@section('header_title', 'Add Administrator')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.admins.index') }}" style="color: inherit; text-decoration: none;">Administrators</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">New Administrator Account</h1>
        <p class="page-subtitle">Provision an administrative login and assign permission roles.</p>
    </div>
</div>

<div class="card" style="max-width: 640px; padding: 1.5rem;">
    <form method="POST" action="{{ route('admin.admins.store') }}">
        @csrf

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Full Name <span style="color: #ef4444;">*</span></label>
            <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
            @error('name')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Email Address <span style="color: #ef4444;">*</span></label>
            <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
            @error('email')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Password <span style="color: #ef4444;">*</span></label>
            <input type="password" name="password" class="form-input @error('password') is-invalid @enderror" required>
            @error('password')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Confirm Password <span style="color: #ef4444;">*</span></label>
            <input type="password" name="password_confirmation" class="form-input" required>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Assign Role(s) <span style="color: #ef4444;">*</span></label>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.25rem;">
                @foreach($roles as $role)
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #334155; cursor: pointer;">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                        <span style="font-weight: 500;">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles')
                <div style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #334155; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                <span>Account Active (Allowed to log in)</span>
            </label>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn btn-primary">Create Administrator</button>
            <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
