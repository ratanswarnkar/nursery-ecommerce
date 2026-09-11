@extends('errors.layout')

@section('title', '419 - Page Expired')

@section('content')
    <span class="badge">Error 419</span>
    <div class="icon-wrap">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h1>Session Expired</h1>
    <p>Your security session has expired due to inactivity. Please refresh the page and try submitting your request again.</p>
    <div class="actions">
        <a href="javascript:window.location.reload();" class="btn btn-primary">Refresh Page</a>
        <a href="{{ route('home') }}" class="btn btn-secondary">Return to Homepage</a>
    </div>
@endsection
