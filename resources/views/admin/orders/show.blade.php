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
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
            &larr; Back to Orders
        </a>
    </div>
</div>

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
            </div>
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
