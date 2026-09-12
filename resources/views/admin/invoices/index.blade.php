@extends('layouts.admin')

@section('title', 'Tax Invoices')
@section('header_title', 'Invoices')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Invoices</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tax Invoices</h1>
        <p class="page-subtitle">View, verify, and download generated GST tax invoices and customer billing receipts.</p>
    </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Invoices Issued</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-top: 0.375rem;">{{ number_format($metrics['total_count']) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Tax Invoiced (GST)</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #0284c7; margin-top: 0.375rem;">₹{{ number_format($metrics['total_tax'], 2) }}</div>
    </div>
    <div class="card" style="padding: 1.25rem;">
        <div style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">Total Invoiced Value</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; margin-top: 0.375rem;">₹{{ number_format($metrics['total_amount'], 2) }}</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.invoices.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Invoice / Order / Customer</label>
            <input type="text" name="search" class="form-input" placeholder="Invoice #, Order #, Customer name..." value="{{ request('search') }}">
        </div>

        <div style="min-width: 150px;">
            <label class="form-label" style="font-size: 0.75rem;">From Date</label>
            <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
        </div>

        <div style="min-width: 150px;">
            <label class="form-label" style="font-size: 0.75rem;">To Date</label>
            <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'date_from', 'date_to']))
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Clear</a>
            @endif
        </div>
    </form>
</div>

<!-- Invoices Table -->
<div class="card" style="overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Invoice Date</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Subtotal</th>
                    <th>Tax (GST)</th>
                    <th>Shipping</th>
                    <th>Grand Total</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td style="font-weight: 600; font-family: monospace; color: #0284c7;">
                            {{ $inv->invoice_number }}
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            {{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '—' }}
                        </td>
                        <td>
                            @if($inv->order)
                                <a href="{{ route('admin.orders.show', $inv->order) }}" style="color: #059669; font-weight: 600; text-decoration: none;">
                                    #{{ $inv->order->order_number }}
                                </a>
                            @else
                                <span style="color: #94a3b8;">N/A</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $cust = $inv->customer_snapshot ?? [];
                            @endphp
                            <div style="font-weight: 500;">{{ $cust['name'] ?? ($inv->order->customer->name ?? 'Valued Customer') }}</div>
                            <div style="font-size: 0.75rem; color: #64748b;">{{ $cust['phone'] ?? ($inv->order->customer->phone ?? '') }}</div>
                        </td>
                        <td style="font-size: 0.8125rem; color: #334155;">
                            ₹{{ number_format($inv->subtotal, 2) }}
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            ₹{{ number_format($inv->tax_amount, 2) }}
                        </td>
                        <td style="font-size: 0.8125rem; color: #64748b;">
                            ₹{{ number_format($inv->shipping_amount, 2) }}
                        </td>
                        <td style="font-weight: 700; color: #0f172a;">
                            ₹{{ number_format($inv->grand_total, 2) }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.invoices.download', $inv) }}" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                <svg style="width: 0.875rem; height: 0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                PDF
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2.5rem 1rem; color: #64748b;">
                            No tax invoices found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div style="padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection
