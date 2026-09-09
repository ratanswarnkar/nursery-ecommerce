@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)
@section('header_title', 'Order Details')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.orders.index') }}" style="color: inherit; text-decoration: none;">Orders</a>
    <span class="breadcrumbs-sep">/</span>
    <span>#{{ $order->order_number }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title" style="font-family: monospace;">Order #{{ $order->order_number }}</h1>
        <p class="page-subtitle">Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        @if(app(\App\Services\Invoice\InvoiceService::class)->canGenerateInvoice($order))
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                View Invoice (PDF)
            </a>
        @endif
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
            &larr; Back to Orders
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
        {{ session('error') }}
    </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Left Column: Items & Totals -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card">
            <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                Line Items ({{ $order->items->count() }})
            </h2>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th>Tax</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <div style="font-weight: 600;">{{ $item->product_name }}</div>
                                    @if($item->variant_name)
                                        <div style="font-size: 0.75rem; color: #6b7280;">{{ $item->variant_name }}</div>
                                    @endif
                                </td>
                                <td style="font-family: monospace; font-size: 0.75rem;">
                                    {{ $item->sku }}
                                </td>
                                <td>₹{{ number_format((float) $item->price, 2) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>₹{{ number_format((float) $item->subtotal, 2) }}</td>
                                <td>₹{{ number_format((float) $item->tax_amount, 2) }}</td>
                                <td style="font-weight: 700;">₹{{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.5rem; border-top: 1px solid #e5e7eb; padding-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; max-width: 280px; margin-left: auto;">
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: #6b7280;">Subtotal:</span>
                    <span style="font-weight: 600;">₹{{ number_format((float) $order->subtotal, 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: #6b7280;">Tax:</span>
                    <span style="font-weight: 600;">₹{{ number_format((float) $order->tax_amount, 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: #6b7280;">Shipping:</span>
                    <span style="font-weight: 600;">₹{{ number_format((float) $order->shipping_amount, 2) }}</span>
                </div>
                @if(bccomp((string) $order->discount_amount, '0.00', 2) > 0)
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: #059669;">
                        <span>Discount:</span>
                        <span style="font-weight: 600;">-₹{{ number_format((float) $order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div style="display: flex; justify-content: space-between; font-size: 1.125rem; font-weight: 800; border-top: 1px solid #e5e7eb; padding-top: 0.5rem; color: #064e3b;">
                    <span>Grand Total:</span>
                    <span>₹{{ number_format((float) $order->grand_total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Transactions -->
        <div class="card">
            <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Payment Transactions ({{ $order->paymentTransactions->count() }})</span>
            </h2>

            @if($order->paymentTransactions->isEmpty())
                <p style="font-size: 0.8125rem; color: #6b7280; font-style: italic; margin: 0.5rem 0;">
                    No payment transactions initiated for this order yet.
                </p>
            @else
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Transaction #</th>
                                <th>Gateway</th>
                                <th>Gateway Ref</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->paymentTransactions as $txn)
                                <tr>
                                    <td style="font-family: monospace; font-size: 0.75rem; font-weight: 600;">
                                        {{ $txn->transaction_number }}
                                    </td>
                                    <td>
                                        <span style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #4b5563;">
                                            {{ $txn->gateway }}
                                        </span>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.75rem; color: #6b7280;">
                                        {{ $txn->gateway_transaction_id ?? '—' }}
                                    </td>
                                    <td style="font-weight: 700;">
                                        ₹{{ number_format((float) $txn->amount, 2) }}
                                    </td>
                                    <td>
                                        @php
                                            $badgeStyle = match($txn->status) {
                                                \App\Enums\PaymentStatus::PAID => 'background: #d1fae5; color: #065f46;',
                                                \App\Enums\PaymentStatus::FAILED => 'background: #fee2e2; color: #991b1b;',
                                                \App\Enums\PaymentStatus::CANCELLED, \App\Enums\PaymentStatus::EXPIRED => 'background: #f3f4f6; color: #374151;',
                                                default => 'background: #fef3c7; color: #92400e;',
                                            };
                                        @endphp
                                        <span class="badge" style="{{ $badgeStyle }} padding: 0.2rem 0.5rem; border-radius: 9999px; font-weight: 700; text-transform: uppercase; font-size: 0.7rem;">
                                            {{ $txn->status->value }}
                                        </span>
                                        @if($txn->failure_message)
                                            <div style="font-size: 0.7rem; color: #dc2626; margin-top: 0.25rem;">
                                                {{ $txn->failure_message }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="font-size: 0.75rem; color: #4b5563;">
                                        {{ $txn->payment_method ?? '—' }}
                                    </td>
                                    <td style="font-size: 0.75rem; color: #6b7280; font-family: monospace;">
                                        {{ ($txn->paid_at ?? $txn->created_at)->format('M d, Y h:i A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($order->notes)
            <div class="card">
                <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.5rem;">Customer Order Notes</h3>
                <p style="font-size: 0.8125rem; color: #374151; background: #f9fafb; padding: 0.75rem; border-radius: 0.5rem;">
                    {{ $order->notes }}
                </p>
            </div>
        @endif

        @if($order->statusHistories->isNotEmpty())
            <div class="card">
                <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem;">Status History Audit</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach($order->statusHistories as $history)
                        <div style="font-size: 0.75rem; border-left: 2px solid #059669; padding-left: 0.75rem;">
                            <div style="font-weight: 700; text-transform: uppercase;">{{ $history->to_status }}</div>
                            @if($history->comment)
                                <div style="color: #4b5563;">{{ $history->comment }}</div>
                            @endif
                            <div style="color: #9ca3af; font-family: monospace; font-size: 0.7rem;">{{ $history->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Right Column: Status & Snapshots -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem;">Order Metadata</h3>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem;">
                <div>
                    <span style="color: #6b7280;">Order Status:</span>
                    <span class="badge" style="background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 9999px; font-weight: 700; text-transform: uppercase;">
                        {{ $order->status->value }}
                    </span>
                </div>
                <div>
                    <span style="color: #6b7280;">Payment Status:</span>
                    <span class="badge" style="background: #f3f4f6; color: #374151; padding: 0.2rem 0.5rem; border-radius: 9999px; font-weight: 700; text-transform: uppercase;">
                        {{ $order->payment_status->value }}
                    </span>
                </div>
                <div>
                    <span style="color: #6b7280;">Fulfillment:</span>
                    <span style="font-weight: 600;">{{ $order->shipping_status->value }}</span>
                </div>
                @if(app(\App\Services\Invoice\InvoiceService::class)->canGenerateInvoice($order))
                    <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e5e7eb;">
                        <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 600; color: #059669; text-decoration: none; font-size: 0.8125rem;">
                            <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Tax Invoice (PDF)
                        </a>
                    </div>
                @else
                    <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e5e7eb; font-size: 0.75rem; color: #9ca3af;">
                        Tax invoice unavailable (order unpaid/ineligible)
                    </div>
                @endif
            </div>
        </div>

        <!-- Fulfillment & Shipping -->
        <div class="card">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Fulfillment & Shipping</span>
                <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 0.15rem 0.45rem; border-radius: 9999px; font-weight: 700; text-transform: uppercase; font-size: 0.65rem;">
                    {{ $order->shipping_status->value }}
                </span>
            </h3>

            @php
                $activeShipment = $order->shipments->sortByDesc('id')->first();
            @endphp

            @if($activeShipment)
                <div style="display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.8125rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 0.4rem 0.6rem; border-radius: 0.375rem;">
                        <span style="font-weight: 700; color: #166534;">Shipment #{{ $activeShipment->id }}</span>
                        <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; padding: 0.15rem 0.45rem; border-radius: 9999px;">
                            {{ $activeShipment->shipping_status->value }}
                        </span>
                    </div>

                    <div>
                        <span style="color: #6b7280;">Order Number:</span>
                        <span style="font-family: monospace; font-weight: 600; color: #111827;">#{{ $order->order_number }}</span>
                    </div>
                    <div>
                        <span style="color: #6b7280;">Carrier / Courier:</span>
                        <span style="font-weight: 600; color: #111827;">{{ $activeShipment->carrier ?: 'Standard Delivery' }}</span>
                    </div>
                    <div>
                        <span style="color: #6b7280;">Tracking / AWB #:</span>
                        <span style="font-family: monospace; font-weight: 700; color: #111827;">{{ $activeShipment->tracking_number ?: 'Not assigned' }}</span>
                    </div>

                    @if($activeShipment->tracking_url)
                        <div style="margin: 0.25rem 0;">
                            <a href="{{ $activeShipment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; padding: 0.3rem 0.6rem; color: #047857; text-decoration: none; border: 1px solid #a7f3d0; background: #ecfdf5; border-radius: 0.375rem; font-weight: 600;">
                                <svg style="width: 0.85rem; height: 0.85rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Track Package</span>
                            </a>
                        </div>
                    @endif

                    <div>
                        <span style="color: #6b7280;">Dispatched At:</span>
                        <span style="font-family: monospace; font-size: 0.75rem;">{{ $activeShipment->shipped_at ? $activeShipment->shipped_at->format('M d, Y h:i A') : '—' }}</span>
                    </div>
                    <div>
                        <span style="color: #6b7280;">Est. Delivery:</span>
                        <span style="font-family: monospace; font-size: 0.75rem; color: #4338ca;">
                            {{ $activeShipment->estimated_delivery_at ? $activeShipment->estimated_delivery_at->format('M d, Y') : '—' }}
                        </span>
                    </div>
                    @if($activeShipment->delivered_at)
                        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">
                            ✓ Delivered on {{ $activeShipment->delivered_at->format('M d, Y h:i A') }}
                        </div>
                    @endif
                    @if($activeShipment->notes)
                        <div style="background: #f9fafb; padding: 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; color: #4b5563;">
                            <span style="font-weight: 600;">Shipment Notes:</span> {{ $activeShipment->notes }}
                        </div>
                    @endif

                    {{-- Shipped Item Snapshot --}}
                    @if(!empty($activeShipment->items_snapshot))
                        <div style="margin-top: 0.5rem; border-top: 1px dashed #e5e7eb; padding-top: 0.6rem;">
                            <h4 style="font-size: 0.75rem; font-weight: 700; color: #374151; margin-bottom: 0.4rem; text-transform: uppercase;">
                                Shipped Items ({{ count($activeShipment->items_snapshot) }})
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                @foreach($activeShipment->items_snapshot as $snapItem)
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f9fafb; padding: 0.4rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem;">
                                        <div>
                                            <span style="font-weight: 600; color: #111827;">{{ $snapItem['product_name'] ?? 'Product' }}</span>
                                            @if(!empty($snapItem['variant_name']))
                                                <span style="color: #6b7280;">({{ $snapItem['variant_name'] }})</span>
                                            @endif
                                            @if(!empty($snapItem['sku']))
                                                <span style="font-family: monospace; color: #9ca3af; font-size: 0.7rem;">[{{ $snapItem['sku'] }}]</span>
                                            @endif
                                        </div>
                                        <span style="font-weight: 700; color: #065f46;">Qty: {{ $snapItem['quantity'] ?? 1 }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Edit Tracking Information Form (Authorized Admins) --}}
                    @can('orders.update', 'admin')
                        <details style="margin-top: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.5rem; background: #f8fafc;">
                            <summary style="font-size: 0.75rem; font-weight: 700; color: #0f766e; cursor: pointer; user-select: none;">
                                Edit Tracking Information
                            </summary>
                            <form method="POST" action="{{ route('admin.orders.shipments.update', [$order, $activeShipment]) }}" style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.45rem;">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label style="display: block; font-size: 0.7rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                        Carrier / Courier:
                                    </label>
                                    <input type="text" name="carrier" value="{{ old('carrier', $activeShipment->carrier) }}" placeholder="e.g. BlueDart, Delhivery" style="width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.75rem;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.7rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                        Tracking / AWB Number:
                                    </label>
                                    <input type="text" name="tracking_number" value="{{ old('tracking_number', $activeShipment->tracking_number) }}" placeholder="e.g. BD123456789IN" style="width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.75rem; font-family: monospace;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.7rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                        Tracking URL:
                                    </label>
                                    <input type="url" name="tracking_url" value="{{ old('tracking_url', $activeShipment->tracking_url) }}" placeholder="https://track.courier.com/..." style="width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.75rem;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.7rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                        Estimated Delivery Date:
                                    </label>
                                    <input type="date" name="estimated_delivery_at" value="{{ old('estimated_delivery_at', $activeShipment->estimated_delivery_at?->format('Y-m-d')) }}" style="width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.75rem;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.7rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem;">
                                        Shipment Notes:
                                    </label>
                                    <input type="text" name="notes" value="{{ old('notes', $activeShipment->notes) }}" placeholder="e.g. Fragile botanical packaging" style="width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.75rem;">
                                </div>
                                <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.75rem; padding: 0.4rem; margin-top: 0.25rem;">
                                    Save Tracking Info
                                </button>
                            </form>
                        </details>
                    @endcan

                    {{-- Admin Milestone Actions for Shipped orders (Step 2-E) --}}
                    @can('orders.update', 'admin')
                        @if($order->status === \App\Enums\OrderStatus::SHIPPED)
                            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; border-top: 1px dashed #e5e7eb; padding-top: 0.75rem;">
                                <form method="POST" action="{{ route('admin.orders.shipments.out-for-delivery', [$order, $activeShipment]) }}" style="flex: 1;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.75rem; padding: 0.4rem;">
                                        Out for Delivery
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.orders.shipments.delivered', [$order, $activeShipment]) }}" style="flex: 1;" onsubmit="return confirm('Confirm shipment delivery to customer?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 0.75rem; padding: 0.4rem;">
                                        Mark Delivered
                                    </button>
                                </form>
                            </div>
                        @elseif($order->status === \App\Enums\OrderStatus::OUT_FOR_DELIVERY)
                            <div style="margin-top: 0.5rem; border-top: 1px dashed #e5e7eb; padding-top: 0.75rem;">
                                <form method="POST" action="{{ route('admin.orders.shipments.delivered', [$order, $activeShipment]) }}" onsubmit="return confirm('Confirm shipment delivery to customer?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 0.75rem; padding: 0.4rem;">
                                        Mark Delivered
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endcan
                </div>
            @else
                @if($order->status === \App\Enums\OrderStatus::PROCESSING && $order->payment_status === \App\Enums\PaymentStatus::PAID)
                    @can('orders.update', 'admin')
                        <form method="POST" action="{{ route('admin.orders.shipments.create', $order) }}" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @csrf
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.2rem;">
                                    Carrier / Courier:
                                </label>
                                <input type="text" name="carrier" placeholder="e.g. BlueDart, Delhivery, DTDC" value="{{ old('carrier') }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.2rem;">
                                    Tracking / AWB Number:
                                </label>
                                <input type="text" name="tracking_number" placeholder="e.g. BD123456789IN" value="{{ old('tracking_number') }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem; font-family: monospace;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.2rem;">
                                    Tracking URL:
                                </label>
                                <input type="url" name="tracking_url" placeholder="https://track.courier.com/..." value="{{ old('tracking_url') }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.2rem;">
                                    Estimated Delivery Date:
                                </label>
                                <input type="date" name="estimated_delivery_at" value="{{ old('estimated_delivery_at') }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.2rem;">
                                    Shipment Notes (optional):
                                </label>
                                <input type="text" name="notes" placeholder="e.g. Fragile botanical packaging" value="{{ old('notes') }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 0.8125rem; padding: 0.5rem; margin-top: 0.25rem;">
                                Dispatch Order & Create Shipment
                            </button>
                        </form>
                    @else
                        <p style="font-size: 0.75rem; color: #6b7280; font-style: italic;">
                            Awaiting fulfillment dispatch by authorized admin.
                        </p>
                    @endcan
                @elseif($order->status->isTerminal())
                    <p style="font-size: 0.75rem; color: #9ca3af; font-style: italic;">
                        Order is in terminal state [{{ $order->status->value }}]. No shipments can be created.
                    </p>
                @else
                    <p style="font-size: 0.75rem; color: #9ca3af; font-style: italic;">
                        Fulfillment is available once payment is verified and order reaches PROCESSING status.
                    </p>
                @endif
            @endif
        </div>

        <!-- Order Lifecycle Management -->
        <div class="card">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
                Order Lifecycle Management
            </h3>

            @if($order->status->isTerminal())
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; color: #374151;">
                    <div style="font-weight: 700; text-transform: uppercase; color: #111827;">Terminal Status</div>
                    <p style="margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">
                        This order is in terminal fulfillment state <strong>{{ $order->status->value }}</strong>. No further status transitions can be performed.
                    </p>
                    @if($order->status === \App\Enums\OrderStatus::CANCELLED && $order->cancellation)
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; border-top: 1px dashed #d1d5db; padding-top: 0.5rem;">
                            <span style="font-weight: 600;">Cancellation Reason:</span> {{ $order->cancellation->reason }}
                        </div>
                    @endif
                </div>
            @else
                @can('orders.update', 'admin')
                    @php
                        $nonCancelTransitions = array_filter(
                            $availableTransitions ?? [],
                            fn($s) => $s !== \App\Enums\OrderStatus::CANCELLED
                        );
                    @endphp

                    @if(!empty($nonCancelTransitions))
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" style="margin-bottom: 1rem;">
                            @csrf
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                                Transition to Status:
                            </label>
                            <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem; margin-bottom: 0.5rem;" required>
                                <option value="">-- Select Next Status --</option>
                                @foreach($nonCancelTransitions as $transition)
                                    <option value="{{ $transition->value }}">{{ strtoupper($transition->value) }}</option>
                                @endforeach
                            </select>

                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                                Comment (optional):
                            </label>
                            <input type="text" name="comment" placeholder="Status change reason or note..." style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.8125rem; margin-bottom: 0.75rem;">

                            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 0.8125rem; padding: 0.5rem;">
                                Update Order Status
                            </button>
                        </form>
                    @endif
                @endcan

                @can('orders.cancel', 'admin')
                    @if($order->status->canTransitionTo(\App\Enums\OrderStatus::CANCELLED))
                        <div style="border-top: 1px solid #e5e7eb; padding-top: 0.75rem; margin-top: 0.75rem;">
                            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Are you sure you want to cancel this order? Deducted inventory will be restored automatically.');">
                                @csrf
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #991b1b; margin-bottom: 0.25rem;">
                                    Cancel Order (Restores Stock):
                                </label>
                                <input type="text" name="reason" placeholder="Mandatory cancellation reason..." style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #fca5a5; border-radius: 0.375rem; font-size: 0.8125rem; margin-bottom: 0.5rem;" required>

                                <button type="submit" class="btn" style="width: 100%; font-size: 0.8125rem; padding: 0.5rem; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-weight: 600;">
                                    Cancel Order
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan
            @endif
        </div>

        <div class="card">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem;">Customer Snapshot</h3>
            <div style="font-size: 0.8125rem; display: flex; flex-direction: column; gap: 0.25rem;">
                <div style="font-weight: 700;">{{ $order->customer_name ?: ($order->customer?->name ?? 'Guest') }}</div>
                <div style="font-family: monospace; color: #4b5563;">{{ $order->customer_phone ?: ($order->customer?->phone ?? '—') }}</div>
                @if($order->customer_email)
                    <div style="color: #4b5563;">{{ $order->customer_email }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.75rem;">Shipping Address Snapshot</h3>
            @php $ship = $order->shipping_address_json; @endphp
            <div style="font-size: 0.8125rem; line-height: 1.5;">
                <div style="font-weight: 700;">{{ $ship['recipient_name'] ?? '—' }}</div>
                <div style="font-family: monospace; color: #4b5563;">{{ $ship['phone'] ?? '—' }}</div>
                <div>{{ $ship['address_line_1'] ?? '' }}@if(!empty($ship['address_line_2'])), {{ $ship['address_line_2'] }}@endif</div>
                <div>{{ $ship['city'] ?? '' }}, {{ $ship['state'] ?? '' }} - {{ $ship['postal_code'] ?? '' }}</div>
                <div style="color: #6b7280;">{{ $ship['country'] ?? 'India' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
