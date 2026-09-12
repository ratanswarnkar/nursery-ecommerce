@extends('layouts.admin')

@section('title', 'Shipments & Logistics')
@section('header_title', 'Shipments')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Shipments</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Order Shipments & Tracking</h1>
        <p class="page-subtitle">Track dispatch details, courier carriers, AWB tracking numbers, and delivery milestones.</p>
    </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Shipments</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 0.375rem;">{{ number_format($metrics['total']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Delivered (Fulfilled)</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.375rem;">{{ number_format($metrics['fulfilled']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">In Transit / Partial</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #2563eb; margin-top: 0.375rem;">{{ number_format($metrics['in_progress']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Returned Shipments</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-top: 0.375rem;">{{ number_format($metrics['returned']) }}</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.shipments.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Tracking / Carrier / Order</label>
            <input type="text" name="search" class="form-input" placeholder="Tracking #, Carrier (Delhivery, etc), Order #..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Fulfillment Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $st->value)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Shipments Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tracking # / AWB</th>
                    <th>Carrier</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Shipped At</th>
                    <th>Delivered At</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $shipment)
                    <tr>
                        <td style="font-weight: 600; font-family: monospace;">
                            @if($shipment->tracking_url)
                                <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline;">
                                    {{ $shipment->tracking_number }} ↗
                                </a>
                            @else
                                {{ $shipment->tracking_number }}
                            @endif
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #334155;">{{ $shipment->carrier ?: 'Standard Courier' }}</span>
                        </td>
                        <td>
                            @if($shipment->order)
                                <a href="{{ route('admin.orders.show', $shipment->order) }}" style="color: #059669; font-weight: 600; text-decoration: none;">
                                    #{{ $shipment->order->order_number }}
                                </a>
                            @else
                                <span style="color: #94a3b8;">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($shipment->order && $shipment->order->customer)
                                <div style="font-weight: 500;">{{ $shipment->order->customer->name }}</div>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $shipment->order->customer->phone }}</div>
                            @elseif($shipment->order)
                                <div style="font-weight: 500;">{{ $shipment->order->shipping_address['name'] ?? 'Guest Customer' }}</div>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusStyles = [
                                    'unfulfilled' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                    'partially_fulfilled' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                    'fulfilled' => ['bg' => '#dcfce7', 'text' => '#166534'],
                                    'returned' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                ];
                                $s = $statusStyles[$shipment->shipping_status->value] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
                            @endphp
                            <span style="display: inline-block; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: {{ $s['bg'] }}; color: {{ $s['text'] }}; text-transform: uppercase;">
                                {{ str_replace('_', ' ', $shipment->shipping_status->value) }}
                            </span>
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $shipment->shipped_at ? $shipment->shipped_at->format('d M Y, h:i A') : 'Pending' }}
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $shipment->delivered_at ? $shipment->delivered_at->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td style="text-align: right;">
                            @if($shipment->order)
                                <a href="{{ route('admin.orders.show', $shipment->order) }}" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem;">
                                    View Order
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No shipments found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($shipments->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $shipments->links() }}
        </div>
    @endif
</div>
@endsection
