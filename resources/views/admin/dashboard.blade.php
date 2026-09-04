@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('header_title', 'Administrative Overview')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, {{ auth('admin')->user()->name }}</h1>
        <p class="page-subtitle">Here is the real-time operational status of your catalog and system.</p>
    </div>
    <div>
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <span>Add Product</span>
            </a>
        @endcan
    </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Total Products</div>
            <div class="stat-value">{{ number_format($totalProducts) }}</div>
            <div style="font-size: 0.75rem; color: #34d399; margin-top: 0.25rem;">{{ $activeProducts }} Active</div>
        </div>
        <div class="stat-icon-wrapper">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Categories</div>
            <div class="stat-value">{{ number_format($totalCategories) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Taxonomy Tree</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Brands</div>
            <div class="stat-value">{{ number_format($totalBrands) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Catalog Makers</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Low Stock Alerts</div>
            <div class="stat-value" style="color: {{ $lowStockCount > 0 ? '#f87171' : 'var(--text-main)' }};">{{ number_format($lowStockCount) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">At or below safety stock</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.15); color: #f87171;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
    </div>
</div>

<!-- Recent Catalog Activity -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Catalog Activity</h2>
        <span class="badge badge-neutral">Audited Log Events</span>
    </div>

    @if($recentActivity->isEmpty())
        <div class="empty-state" style="padding: 2rem 1rem;">
            <div class="empty-title">No recent catalog activity</div>
            <div class="empty-desc">Any changes made to products, categories, or brands will appear here with actor audit logs.</div>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Admin User</th>
                        <th>Details</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentActivity as $log)
                        <tr>
                            <td>
                                <span class="badge badge-primary">{{ str_replace('catalog.', '', $log->action) }}</span>
                            </td>
                            <td style="font-weight: 500;">
                                {{ $log->admin?->name ?? 'System' }}
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.8125rem;">
                                @if(isset($log->new_values['name']))
                                    Name: <strong style="color: var(--text-main);">{{ $log->new_values['name'] }}</strong>
                                @elseif(isset($log->new_values['sku']))
                                    SKU: <strong style="color: var(--text-main);">{{ $log->new_values['sku'] }}</strong>
                                @else
                                    ID: {{ $log->auditable_id }}
                                @endif
                            </td>
                            <td style="color: var(--text-dim); font-size: 0.75rem;">
                                {{ $log->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
