@extends('errors.layout')

@section('title', '404 - Page Not Found')

@section('content')
    <span class="badge">Error 404</span>
    <div class="icon-wrap">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>
    <h1>Plant or Page Not Found</h1>
    <p>The page you are looking for may have been pruned, renamed, or is temporarily unavailable in our nursery catalog.</p>
    <div class="actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Return to Homepage</a>
        <a href="{{ route('shop.index') }}" class="btn btn-secondary">Explore Plant Catalog</a>
    </div>
@endsection
