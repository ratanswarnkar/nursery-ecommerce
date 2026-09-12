@extends('layouts.admin')

@section('title', 'Order Management')
@section('header_title', 'Orders')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Orders</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Customer Orders</h1>
        <p class="page-subtitle">Track, review, and manage customer nursery orders and fulfillment status.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.orders.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Order / Customer</label>
            <input type="text" name="q" class="form-input" placeholder="Order number, customer name, phone..." value="{{ request('q') }}">
        </div>

        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Order Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach($orderStatuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                        {{ ucfirst($st->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Payment Status</label>
            <select name="payment_status" class="form-select">
                <option value="">All Payments</option>
                @foreach($paymentStatuses as $pst)
                    <option value="{{ $pst->value }}" {{ request('payment_status') === $pst->value ? 'selected' : '' }}>
                        {{ ucfirst($pst->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['q', 'status', 'payment_status']))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;">
                            #{{ $order->order_number }}
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $order->customer_name ?: ($order->customer?->name ?? 'Guest') }}</div>
                            <div style="font-size: 0.75rem; color: #6b7280; font-family: monospace;">{{ $order->customer_phone ?: ($order->customer?->phone ?? '—') }}</div>
                        </td>
                        <td style="font-size: 0.8125rem;">
                            {{ $order->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td>
                            {{ $order->items->count() }}
                        </td>
                        <td style="font-weight: 700; color: #064e3b;">
                            ₹{{ number_format((float) $order->grand_total, 2) }}
                        </td>
                        <td>
                            <span class="badge" style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                {{ $order->status->value }}
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: #f3f4f6; color: #374151; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                {{ $order->payment_status->value }}
                            </span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                                View
                            </a>
                            @if(app(\App\Services\Invoice\InvoiceService::class)->canGenerateInvoice($order))
                                <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.75rem; margin-left: 0.25rem;" title="Download Tax Invoice PDF">
                                    <svg style="width: 0.8rem; height: 0.8rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span>PDF</span>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: #6b7280;">
                            No orders found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb;">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
