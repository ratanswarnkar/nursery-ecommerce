@extends('layouts.admin')

@section('title', 'Customer: ' . $customer->name)
@section('header_title', 'Customer Profile')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.customers.index') }}" style="color: inherit; text-decoration: none;">Customers</a>
    <span class="breadcrumbs-sep">/</span>
    <span>{{ $customer->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $customer->name }}</h1>
        <p class="page-subtitle">Customer account registered on {{ $customer->created_at->format('d M Y, h:i A') }}</p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        @if($customer->is_active)
            <span style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.8125rem; font-weight: 600; background: #dcfce7; color: #15803d; text-transform: uppercase;">
                Active Account
            </span>
        @else
            <span style="display: inline-block; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.8125rem; font-weight: 600; background: #fee2e2; color: #b91c1c; text-transform: uppercase;">
                Suspended / Inactive
            </span>
        @endif

        @can('customers.update', 'admin')
            <form method="POST" action="{{ route('admin.customers.toggle-status', $customer) }}" onsubmit="return confirm('Change status for this customer?');">
                @csrf
                <button type="submit" class="btn {{ $customer->is_active ? 'btn-danger' : 'btn-success' }}">
                    {{ $customer->is_active ? 'Suspend Account' : 'Activate Account' }}
                </button>
            </form>
        @endcan
    </div>
</div>

<!-- Stats Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Orders</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 0.375rem;">{{ number_format($orderStats['total_orders']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Lifetime Value</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.375rem;">₹{{ number_format($orderStats['total_spent'], 2) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Avg Order Value</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #2563eb; margin-top: 0.375rem;">
            ₹{{ $orderStats['total_orders'] > 0 ? number_format($orderStats['total_spent'] / $orderStats['total_orders'], 2) : '0.00' }}
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Profile & Contact Card -->
    <div class="card" style="padding: 1.5rem;">
        <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-bottom: 1rem;">Contact Details</h2>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <div>
                <div style="font-size: 0.75rem; color: #64748b;">Phone (Primary)</div>
                <div style="font-size: 0.9375rem; font-weight: 600; color: #0f172a; font-family: monospace;">{{ $customer->phone }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b;">Email Address</div>
                <div style="font-size: 0.9375rem; color: #0f172a;">{{ $customer->email ?: 'None provided' }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b;">Account ID</div>
                <div style="font-size: 0.8125rem; font-family: monospace; color: #64748b;">#CUST-{{ $customer->id }}</div>
            </div>
            <div>
                <div style="font-size: 0.75rem; color: #64748b;">Joined Date</div>
                <div style="font-size: 0.875rem; color: #0f172a;">{{ $customer->created_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a; margin-top: 1.5rem; margin-bottom: 1rem;">Saved Addresses</h2>
        @forelse($customer->addresses as $addr)
            <div style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; font-size: 0.8125rem; text-transform: uppercase;">{{ $addr->address_type->value ?? 'Address' }}</span>
                    @if($addr->is_default)
                        <span style="font-size: 0.6875rem; font-weight: 600; color: #059669; background: #dcfce7; padding: 0.125rem 0.375rem; border-radius: 9999px;">Default</span>
                    @endif
                </div>
                <div style="font-size: 0.8125rem; color: #475569; margin-top: 0.25rem;">
                    {{ $addr->recipient_name }} ({{ $addr->phone }})<br>
                    {{ $addr->address_line_1 }}@if($addr->address_line_2), {{ $addr->address_line_2 }}@endif<br>
                    {{ $addr->city }}, {{ $addr->state }} - {{ $addr->postal_code }}
                </div>
            </div>
        @empty
            <p style="font-size: 0.8125rem; color: #94a3b8;">No saved addresses found.</p>
        @endforelse
    </div>

    <!-- Orders History Table -->
    <div class="card" style="overflow: hidden;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #0f172a;">Order History ({{ $customer->orders->count() }})</h2>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Fulfillment</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customer->orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" style="color: #059669; font-weight: 600; text-decoration: none;">
                                    #{{ $order->order_number }}
                                </a>
                            </td>
                            <td style="font-size: 0.8125rem; color: #64748b;">
                                {{ $order->created_at->format('d M Y') }}
                            </td>
                            <td>
                                <span style="font-weight: 500;">{{ $order->items_count }} items</span>
                            </td>
                            <td style="font-weight: 700; color: #0f172a;">
                                ₹{{ number_format($order->grand_total, 2) }}
                            </td>
                            <td>
                                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                    {{ $order->payment_status->value }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                    {{ $order->status->value }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem 1rem; color: #64748b;">
                                This customer has not placed any orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
