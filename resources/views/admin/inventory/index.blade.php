@extends('layouts.admin')

@section('title', 'Inventory Management')
@section('header_title', 'Inventory & Stock')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <span>Inventory</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Inventory Stock Levels</h1>
        <p class="page-subtitle">Track physical stock, reservations, and safety thresholds across warehouses.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('admin.inventory.movements') }}" class="btn btn-secondary">
            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            <span>Stock Ledger</span>
        </a>
        @can('warehouses.view', 'admin')
            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-secondary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                <span>Warehouses</span>
            </a>
        @endcan
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('admin.inventory.index') }}" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label class="form-label" style="font-size: 0.75rem;">Search Product / SKU</label>
            <input type="text" name="search" class="form-input" placeholder="Search by SKU or name..." value="{{ request('search') }}">
        </div>

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

        <div style="display: flex; align-items: center; padding-bottom: 0.5rem;">
            <label class="form-check" style="cursor: pointer;">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()">
                <span style="font-size: 0.8125rem; font-weight: 600; color: #f59e0b;">Low Stock Alert Only</span>
            </label>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['search', 'warehouse_id', 'low_stock']))
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Reset</a>
            @endif
        </div>
    </form>
</div>

@if($inventories->isEmpty())
    <div class="card empty-state">
        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        <div class="empty-title">No inventory records found</div>
        <div class="empty-desc">Inventory records are created when variants are stocked in active warehouses.</div>
    </div>
@else
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product / Variant</th>
                    <th>Warehouse</th>
                    <th style="text-align: right;">Total Qty</th>
                    <th style="text-align: right;">Reserved</th>
                    <th style="text-align: right;">Available</th>
                    <th style="text-align: right;">Safety Stock</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inventories as $inv)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);">
                                {{ $inv->productVariant?->product?->name ?? 'Unknown Product' }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                SKU: {{ $inv->productVariant?->sku ?? '—' }}
                                @if($inv->productVariant && $inv->productVariant->attributeValues->isNotEmpty())
                                    | {{ $inv->productVariant->attributeValues->pluck('value')->implode(' / ') }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-main);">
                                {{ $inv->warehouse?->name ?? '—' }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                {{ $inv->warehouse?->code }}
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 600; font-family: monospace;">
                            {{ $inv->quantity }}
                        </td>
                        <td style="text-align: right; color: var(--text-muted); font-family: monospace;">
                            {{ $inv->reserved_quantity }}
                        </td>
                        <td style="text-align: right; font-weight: 700; font-family: monospace; color: {{ $inv->available_quantity <= 0 ? '#ef4444' : ($inv->is_low_stock ? '#f59e0b' : '#10b981') }};">
                            {{ $inv->available_quantity }}
                        </td>
                        <td style="text-align: right; color: var(--text-muted); font-family: monospace;">
                            {{ $inv->safety_stock }}
                        </td>
                        <td>
                            @if($inv->available_quantity <= 0)
                                <span class="badge badge-danger">Out of Stock</span>
                            @elseif($inv->is_low_stock)
                                <span class="badge badge-warning" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                                    Low Stock
                                </span>
                            @else
                                <span class="badge badge-success">In Stock</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            @can('inventory.manage', 'admin')
                                <button type="button" class="btn btn-secondary btn-sm"
                                    onclick="openAdjustModal({{ $inv->product_variant_id }}, {{ $inv->warehouse_id }}, '{{ addslashes($inv->productVariant?->product?->name . ' - ' . $inv->productVariant?->sku) }}', '{{ addslashes($inv->warehouse?->name) }}', {{ $inv->quantity }}, {{ $inv->safety_stock }})">
                                    Adjust Stock
                                </button>
                            @endcan
                            <a href="{{ route('admin.inventory.movements', ['product_variant_id' => $inv->product_variant_id, 'warehouse_id' => $inv->warehouse_id]) }}"
                               class="btn btn-secondary btn-sm" title="View Movements">
                                Ledger
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $inventories->links() }}
    </div>
@endif

<!-- Adjust Stock Modal -->
<div id="adjustModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 520px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); margin: 1rem;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title">Adjust Stock Level</h2>
            <button type="button" onclick="closeAdjustModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.inventory.adjust') }}">
            @csrf
            <input type="hidden" name="product_variant_id" id="modal_variant_id">
            <input type="hidden" name="warehouse_id" id="modal_warehouse_id">

            <div style="padding: 0 0 1rem 0; border-bottom: 1px solid var(--border-color); margin-bottom: 1rem;">
                <div style="font-weight: 600; font-size: 0.9375rem; color: var(--text-main);" id="modal_product_title"></div>
                <div style="font-size: 0.8125rem; color: var(--text-muted);" id="modal_warehouse_title"></div>
                <div style="font-size: 0.8125rem; color: var(--primary-light); margin-top: 0.25rem;" id="modal_current_qty"></div>
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label required">Adjustment Mode</label>
                    <select name="adjustment_mode" id="modal_adjustment_mode" class="form-select" onchange="toggleAdjustmentMode()">
                        <option value="delta">Add / Deduct (+/- Change)</option>
                        <option value="set">Set Exact Count (Count Audit)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required" id="modal_quantity_label">Quantity</label>
                    <input type="number" name="quantity" id="modal_quantity" class="form-input" required placeholder="e.g., +10 or -5">
                    <div class="form-hint" id="modal_quantity_hint">Use positive for intake, negative for deduction.</div>
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label">Movement Type / Reason</label>
                    <select name="type" class="form-select">
                        @foreach($movementTypes as $type)
                            <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Safety Stock Threshold</label>
                    <input type="number" name="safety_stock" id="modal_safety_stock" class="form-input" min="0" placeholder="e.g., 5">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Notes / Reference</label>
                <textarea name="notes" class="form-textarea" rows="2" placeholder="Reason for adjustment, PO number, or cycle count reference..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjustModal(variantId, warehouseId, productTitle, warehouseTitle, currentQty, safetyStock) {
    document.getElementById('modal_variant_id').value = variantId;
    document.getElementById('modal_warehouse_id').value = warehouseId;
    document.getElementById('modal_product_title').textContent = productTitle;
    document.getElementById('modal_warehouse_title').textContent = 'Facility: ' + warehouseTitle;
    document.getElementById('modal_current_qty').textContent = 'Current on-hand: ' + currentQty + ' units';
    document.getElementById('modal_safety_stock').value = safetyStock;
    document.getElementById('modal_quantity').value = '';
    document.getElementById('modal_adjustment_mode').value = 'delta';
    toggleAdjustmentMode();

    const modal = document.getElementById('adjustModal');
    modal.style.display = 'flex';
}

function closeAdjustModal() {
    document.getElementById('adjustModal').style.display = 'none';
}

function toggleAdjustmentMode() {
    const mode = document.getElementById('modal_adjustment_mode').value;
    const label = document.getElementById('modal_quantity_label');
    const hint = document.getElementById('modal_quantity_hint');
    const input = document.getElementById('modal_quantity');

    if (mode === 'set') {
        label.textContent = 'New Exact Count';
        hint.textContent = 'Enter the newly verified physical count (>= 0).';
        input.min = '0';
        input.placeholder = 'e.g., 50';
    } else {
        label.textContent = 'Adjustment (+/- Delta)';
        hint.textContent = 'Use positive (+10) for intake, negative (-5) for deduction.';
        input.removeAttribute('min');
        input.placeholder = 'e.g., +10 or -5';
    }
}
</script>
@endsection
