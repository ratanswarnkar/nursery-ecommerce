@extends('errors.layout')

@section('title', '429 - Too Many Requests')

@section('content')
    <span class="badge">Error 429</span>
    <div class="icon-wrap">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
    </div>
    <h1>Too Many Requests</h1>
    <p>You have submitted too many requests in a short period. Please wait a few moments before trying again to protect nursery operations.</p>
    <div class="actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Return to Homepage</a>
        <a href="{{ route('cart.index') }}" class="btn btn-secondary">View Cart</a>
    </div>
@endsection
