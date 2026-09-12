@extends('layouts.admin')

@section('title', 'Payment Transactions')
@section('header_title', 'Payments')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Payments</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Payment Transactions</h1>
        <p class="page-subtitle">Monitor payment activity, gateway transaction IDs, and settlement statuses across customer orders.</p>
    </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Transactions</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 0.375rem;">{{ number_format($metrics['total_count']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Collected (Paid)</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.375rem;">₹{{ number_format($metrics['total_collected'], 2) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Pending Payment</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #d97706; margin-top: 0.375rem;">₹{{ number_format($metrics['total_pending'], 2) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Refunded</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-top: 0.375rem;">₹{{ number_format($metrics['total_refunded'], 2) }}</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.payments.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Transaction / Order</label>
            <input type="text" name="search" class="form-input" placeholder="Transaction #, Gateway ID, Order #..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 160px;">
            <label class="form-label" style="font-size: 0.75rem;">Payment Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                        {{ ucfirst($status->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 140px;">
            <label class="form-label" style="font-size: 0.75rem;">Gateway</label>
            <select name="gateway" class="form-select">
                <option value="">All Gateways</option>
                <option value="razorpay" {{ request('gateway') === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                <option value="cod" {{ request('gateway') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
            </select>
        </div>

        <div style="min-width: 140px;">
            <label class="form-label" style="font-size: 0.75rem;">From Date</label>
            <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
        </div>

        <div style="min-width: 140px;">
            <label class="form-label" style="font-size: 0.75rem;">To Date</label>
            <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'status', 'gateway', 'date_from', 'date_to']))
                <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Txn #</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Gateway</th>
                    <th>Gateway Txn ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                    <tr>
                        <td style="font-weight: 600; font-family: monospace; font-size: 0.8125rem;">
                            {{ $txn->transaction_number }}
                        </td>
                        <td>
                            @if($txn->order)
                                <a href="{{ route('admin.orders.show', $txn->order) }}" style="color: #059669; font-weight: 600; text-decoration: none;">
                                    #{{ $txn->order->order_number }}
                                </a>
                            @else
                                <span style="color: #94a3b8;">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($txn->order && $txn->order->customer)
                                <div style="font-weight: 500;">{{ $txn->order->customer->name }}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $txn->order->customer->phone }}</div>
                            @elseif($txn->order)
                                <div style="font-weight: 500;">{{ $txn->order->shipping_address['name'] ?? 'Guest Customer' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size: 0.8125rem; text-transform: uppercase; font-weight: 600; color: #475569;">
                                {{ $txn->gateway }}
                            </span>
                            @if($txn->payment_method)
                                <div style="font-size: 0.6875rem; color: #94a3b8; text-transform: capitalize;">
                                    {{ str_replace('_', ' ', $txn->payment_method) }}
                                </div>
                            @endif
                        </td>
                        <td style="font-family: monospace; font-size: 0.75rem; color: #475569;">
                            {{ $txn->gateway_transaction_id ?: '—' }}
                        </td>
                        <td style="font-weight: 700; color: #0f172a;">
                            ₹{{ number_format($txn->amount, 2) }}
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'paid' => ['bg' => '#dcfce7', 'text' => '#15803d'],
                                    'pending' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                                    'failed' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
                                    'cancelled' => ['bg' => '#f1f5f9', 'text' => '#475569'],
                                    'expired' => ['bg' => '#f1f5f9', 'text' => '#475569'],
                                    'refunded' => ['bg' => '#ede9fe', 'text' => '#6b21a8'],
                                    'partially_refunded' => ['bg' => '#f3e8ff', 'text' => '#7e22ce'],
                                ];
                                $c = $statusColors[$txn->status->value] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
                            @endphp
                            <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: {{ $c['bg'] }}; color: {{ $c['text'] }}; text-transform: uppercase;">
                                {{ $txn->status->value }}
                            </span>
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $txn->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td style="text-align: right;">
                            @if($txn->order)
                                <a href="{{ route('admin.orders.show', $txn->order) }}" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    View Order
                                </a>
                            @else
                                <span style="color: #94a3b8; font-size: 0.75rem;">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No payment transactions found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
