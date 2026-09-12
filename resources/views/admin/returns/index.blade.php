@extends('layouts.admin')

@section('title', 'Returns & Refunds')
@section('header_title', 'Returns')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Returns & Refunds</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Order Returns & Refunds</h1>
        <p class="page-subtitle">Review customer return requests, inspect return conditions, process restocking, and manage refunds.</p>
    </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Returns</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 0.375rem;">{{ number_format($metrics['total']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Requested (Pending Review)</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #d97706; margin-top: 0.375rem;">{{ number_format($metrics['requested']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Approved</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #2563eb; margin-top: 0.375rem;">{{ number_format($metrics['approved']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Completed</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.375rem;">{{ number_format($metrics['completed']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Rejected</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-top: 0.375rem;">{{ number_format($metrics['rejected']) }}</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.returns.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Return / Order / Customer</label>
            <input type="text" name="search" class="form-input" placeholder="Return ID, Order #, Customer, Reason..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Return Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                        {{ ucfirst($st->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('admin.returns.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Returns Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Return ID</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Item & Qty</th>
                    <th>Reason</th>
                    <th>Refund Est.</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $ret)
                    <tr>
                        <td style="font-weight: 600; font-family: monospace;">
                            #RET-{{ $ret->id }}
                        </td>
                        <td>
                            @if($ret->order)
                                <a href="{{ route('admin.orders.show', $ret->order) }}" style="color: #059669; font-weight: 600; text-decoration: none;">
                                    #{{ $ret->order->order_number }}
                                </a>
                            @else
                                <span style="color: #94a3b8;">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($ret->order && $ret->order->customer)
                                <div style="font-weight: 500;">{{ $ret->order->customer->name }}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $ret->order->customer->phone }}</div>
                            @elseif($ret->order)
                                <div style="font-weight: 500;">{{ $ret->order->shipping_address['name'] ?? 'Guest Customer' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 500; font-size: 0.8125rem;">
                                {{ $ret->orderItem->product_name ?? 'Item' }}
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                Qty: <strong>{{ $ret->quantity }}</strong>
                            </div>
                        </td>
                        <td style="max-width: 200px; font-size: 0.8125rem; color: #475569;">
                            {{ Str::limit($ret->reason, 60) }}
                        </td>
                        <td style="font-weight: 700; color: #0f172a;">
                            {{ $ret->refund_amount ? '₹' . number_format($ret->refund_amount, 2) : '—' }}
                        </td>
                        <td>
                            @php
                                $returnStyles = [
                                    'requested' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                    'approved' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                    'completed' => ['bg' => '#dcfce7', 'text' => '#166534'],
                                    'rejected' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                ];
                                $stStyle = $returnStyles[$ret->status->value] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
                            @endphp
                            <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: {{ $stStyle['bg'] }}; color: {{ $stStyle['text'] }}; text-transform: uppercase;">
                                {{ $ret->status->value }}
                            </span>
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $ret->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td style="text-align: right;">
                            @if($ret->order)
                                <a href="{{ route('admin.orders.show', $ret->order) }}" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    Manage in Order
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No return requests found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($returns->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $returns->links() }}
        </div>
    @endif
</div>
@endsection
