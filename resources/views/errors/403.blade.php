@extends('errors.layout')

@section('title', '403 - Access Forbidden')

@section('content')
    <span class="badge">Error 403</span>
    <div class="icon-wrap">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <h1>Access Restricted</h1>
    <p>You do not have permission to access this protected area of the nursery portal.</p>
    <div class="actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Return to Homepage</a>
        <a href="{{ route('customer.login') }}" class="btn btn-secondary">Customer Login</a>
    </div>
@endsection
