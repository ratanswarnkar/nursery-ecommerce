@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('header_title', 'Central Control Center')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, {{ auth('admin')->user()->name }}</h1>
        <p class="page-subtitle">Real-time operational overview across retail sales, logistics, tenders, customers, and security.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        @can('tenders.create', 'admin')
            <a href="{{ route('admin.tenders.create') }}" class="btn btn-secondary" style="font-size: 0.8125rem;">
                + New Tender
            </a>
        @endcan
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary" style="font-size: 0.8125rem;">
                + Add Product
            </a>
        @endcan
    </div>
</div>

<!-- Primary Commercial & Operational KPIs -->
<div class="stat-grid" style="margin-bottom: 1.5rem;">
    <!-- Revenue -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Collected Revenue</div>
            <div class="stat-value" style="color: #059669;">₹{{ number_format($metrics['total_revenue'], 2) }}</div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">From settled customer transactions</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #059669;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Storefront Orders</div>
            <div class="stat-value">{{ number_format($metrics['total_orders']) }}</div>
            <div style="font-size: 0.75rem; color: #2563eb; margin-top: 0.25rem;">
                {{ $metrics['paid_orders'] }} Paid • {{ $metrics['pending_orders'] }} Pending
            </div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(37, 99, 235, 0.15); color: #2563eb;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
        </div>
    </div>

    <!-- Active Tenders -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Active Tenders (B2B)</div>
            <div class="stat-value">{{ number_format($metrics['active_tenders']) }}</div>
            <div style="font-size: 0.75rem; color: #8b5cf6; margin-top: 0.25rem;">Government & Institutional contracts</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
    </div>

    <!-- Customers -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Registered Customers</div>
            <div class="stat-value">{{ number_format($metrics['total_customers']) }}</div>
            <div style="font-size: 0.75rem; color: #0284c7; margin-top: 0.25rem;">Storefront buyers</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(2, 132, 199, 0.15); color: #0284c7;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        </div>
    </div>
</div>

<!-- Secondary Catalog & Operations KPIs -->
<div class="stat-grid" style="margin-bottom: 2rem;">
    <!-- Catalog Products -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Catalog Products</div>
            <div class="stat-value">{{ number_format($metrics['total_products']) }}</div>
            <div style="font-size: 0.75rem; color: #059669; margin-top: 0.25rem;">{{ $metrics['active_products'] }} Active in Storefront</div>
        </div>
        <div class="stat-icon-wrapper">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Low Stock Alerts</div>
            <div class="stat-value" style="color: {{ $metrics['low_stock_count'] > 0 ? '#dc2626' : 'inherit' }};">{{ number_format($metrics['low_stock_count']) }}</div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">At or below safety threshold</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
    </div>

    <!-- In Transit Shipments -->
    <div class="stat-card">
        <div>
            <div class="stat-label">In-Transit Shipments</div>
            <div class="stat-value">{{ number_format($metrics['in_transit_shipments']) }}</div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Delhi NCR deliveries en route</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
        </div>
    </div>

    <!-- Returns Pending -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Returns Pending Review</div>
            <div class="stat-value" style="color: {{ $metrics['pending_returns'] > 0 ? '#d97706' : 'inherit' }};">{{ number_format($metrics['pending_returns']) }}</div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Awaiting return authorization</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H4m0 0l3-3m-3 3l3 3m5 4v1a3 3 0 003 3h6a3 3 0 003-3V7a3 3 0 00-3-3h-6a3 3 0 00-3 3v1" /></svg>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Recent Customer Orders -->
    <div class="card" style="overflow: hidden;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;">
            <h2 class="card-title" style="font-size: 1rem; font-weight: 600; color: #0f172a; margin: 0;">Recent Orders</h2>
            @can('orders.view', 'admin')
                <a href="{{ route('admin.orders.index') }}" style="font-size: 0.75rem; color: #059669; font-weight: 600; text-decoration: none;">View All →</a>
            @endcan
        </div>

        @if($recentOrders->isEmpty())
            <div style="padding: 2rem; text-align: center; color: #64748b; font-size: 0.8125rem;">
                No customer orders placed yet.
            </div>
        @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $ro)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $ro) }}" style="font-weight: 600; color: #059669; text-decoration: none;">
                                        #{{ $ro->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <span style="font-weight: 500; font-size: 0.8125rem;">{{ $ro->customer->name ?? ($ro->shipping_address['name'] ?? 'Guest') }}</span>
                                </td>
                                <td style="font-weight: 600; font-size: 0.8125rem;">
                                    ₹{{ number_format($ro->grand_total, 2) }}
                                </td>
                                <td>
                                    <span style="font-size: 0.6875rem; font-weight: 600; text-transform: uppercase;">
                                        {{ $ro->status->value }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Recent Audit Logs -->
    <div class="card" style="overflow: hidden;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;">
            <h2 class="card-title" style="font-size: 1rem; font-weight: 600; color: #0f172a; margin: 0;">Recent Audit Events</h2>
            @can('audit-logs.view', 'admin')
                <a href="{{ route('admin.audit-logs.index') }}" style="font-size: 0.75rem; color: #059669; font-weight: 600; text-decoration: none;">View All →</a>
            @endcan
        </div>

        @if($recentActivity->isEmpty())
            <div style="padding: 2rem; text-align: center; color: #64748b; font-size: 0.8125rem;">
                No audit logs recorded yet.
            </div>
        @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Actor</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivity as $log)
                            <tr>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.75rem; font-weight: 600; color: #334155;">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td style="font-size: 0.8125rem;">
                                    {{ $log->admin?->name ?? ($log->customer?->name ?? 'System') }}
                                </td>
                                <td style="font-size: 0.75rem; color: #64748b;">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
