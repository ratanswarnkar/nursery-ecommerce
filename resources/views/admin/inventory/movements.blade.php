@extends('layouts.admin')

@section('title', 'Stock Movement Ledger')
@section('header_title', 'Stock Movements')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.inventory.index') }}">Inventory</a>
    <span class="breadcrumbs-sep">/</span>
    <span>Movements Ledger</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Stock Movement Ledger</h1>
        <p class="page-subtitle">Append-only audit trail recording every inventory intake, deduction, transfer, and count adjustment.</p>
    </div>
    <div>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">
            &larr; Back to Inventory
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.inventory.movements') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Warehouse</label>
            <select name="warehouse_id" class="form-select">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                        {{ $wh->name }} ({{ $wh->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Movement Type</label>
            <select name="type" class="form-select">
                <option value="">All Types</option>
                @foreach(\App\Enums\StockMovementType::cases() as $type)
                    <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $type->value)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['warehouse_id', 'type', 'product_variant_id']))
                <a href="{{ route('admin.inventory.movements') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Reset</a>
            @endif
        </div>
    </form>
</div>

@if($movements->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
        <div class="empty-title">No stock movements recorded</div>
        <div class="empty-desc">Any physical intake, manual adjustment, or fulfillment deduction will appear here permanently.</div>
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Product / Variant</th>
                    <th>Warehouse</th>
                    <th>Movement Type</th>
                    <th style="text-align: right;">Quantity</th>
                    <th style="text-align: right;">Before &rarr; After</th>
                    <th>Notes / Audit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    <tr>
                        <td style="font-size: 0.8125rem; color: var(--text-muted); white-space: nowrap;">
                            {{ $m->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);">
                                {{ $m->productVariant?->product?->name ?? 'Variant #' . $m->product_variant_id }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                SKU: {{ $m->productVariant?->sku ?? '—' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-main);">
                                {{ $m->warehouse?->name ?? '—' }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                {{ $m->warehouse?->code }}
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="
                                @if(in_array($m->type->value ?? $m->type, ['purchase_receive', 'intake', 'return_restock']))
                                    background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);
                                @elseif(in_array($m->type->value ?? $m->type, ['damage', 'loss', 'expired', 'discard']))
                                    background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);
                                @elseif(in_array($m->type->value ?? $m->type, ['order_fulfillment', 'tender_fulfillment']))
                                    background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3);
                                @else
                                    background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);
                                @endif
                            ">
                                {{ ucfirst(str_replace('_', ' ', $m->type->value ?? $m->type)) }}
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 700; font-family: monospace; color: {{ $m->quantity > 0 ? '#10b981' : ($m->quantity < 0 ? '#ef4444' : 'var(--text-muted)') }};">
                            {{ $m->quantity > 0 ? '+' . $m->quantity : $m->quantity }}
                        </td>
                        <td style="text-align: right; font-family: monospace; font-size: 0.8125rem; color: var(--text-muted); white-space: nowrap;">
                            {{ $m->previous_quantity }} &rarr; <span style="font-weight: 600; color: var(--text-main);">{{ $m->new_quantity }}</span>
                        </td>
                        <td style="font-size: 0.8125rem; color: var(--text-muted); max-width: 250px;">
                            @if($m->notes)
                                <div>{{ $m->notes }}</div>
                            @endif
                            @if($m->reference_type)
                                <div style="font-size: 0.75rem; color: var(--text-dim); font-family: monospace;">
                                    Ref: {{ class_basename($m->reference_type) }} #{{ $m->reference_id }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $movements->links() }}
    </div>
@endif
@endsection
