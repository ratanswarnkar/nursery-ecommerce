@extends('layouts.admin')

@section('title', 'Customers')
@section('header_title', 'Customers')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Customers</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Storefront Customers</h1>
        <p class="page-subtitle">View customer profiles, contact numbers, order volumes, lifetime values, and account statuses.</p>
    </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Customers</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 0.375rem;">{{ number_format($metrics['total_customers']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Active Accounts</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.375rem;">{{ number_format($metrics['active_customers']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Suspended / Inactive</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-top: 0.375rem;">{{ number_format($metrics['inactive_customers']) }}</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.customers.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Customer</label>
            <input type="text" name="search" class="form-input" placeholder="Name, Phone (+91), or Email..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 160px;">
            <label class="form-label" style="font-size: 0.75rem;">Account Status</label>
            <select name="is_active" class="form-select">
                <option value="">All Statuses</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive / Suspended</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'is_active']))
                <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Customers Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Lifetime Spent</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>
                            <a href="{{ route('admin.customers.show', $customer) }}" style="font-weight: 600; color: #0f172a; text-decoration: none;">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td style="font-family: monospace; font-size: 0.8125rem;">
                            {{ $customer->phone }}
                        </td>
                        <td style="color: #475569; font-size: 0.8125rem;">
                            {{ $customer->email ?: '—' }}
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #2563eb;">{{ $customer->orders_count }}</span> orders
                        </td>
                        <td style="font-weight: 700; color: #059669;">
                            ₹{{ number_format($customer->orders_sum_grand_total ?? 0, 2) }}
                        </td>
                        <td>
                            @if($customer->is_active)
                                <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #15803d; text-transform: uppercase;">
                                    Active
                                </span>
                            @else
                                <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fee2e2; color: #b91c1c; text-transform: uppercase;">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $customer->created_at->format('d M Y') }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 0.375rem;">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    View
                                </a>
                                @can('customers.update', 'admin')
                                    <form method="POST" action="{{ route('admin.customers.toggle-status', $customer) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to {{ $customer->is_active ? 'deactivate' : 'activate' }} this customer account?');">
                                        @csrf
                                        <button type="submit" class="btn {{ $customer->is_active ? 'btn-danger' : 'btn-success' }}" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                            {{ $customer->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No customers found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
