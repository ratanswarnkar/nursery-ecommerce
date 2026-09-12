@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('header_title', 'Business Operations & Analytics')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Operations Dashboard</h1>
        <p class="page-subtitle">Real-time business analytics across retail catalog, inventory, payments, tenders, and fulfillment.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        @can('products.create', 'admin')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary" style="font-size: 0.8125rem;">
                + Add Product
            </a>
        @endcan
        @can('inventory.manage', 'admin')
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary" style="font-size: 0.8125rem;">
                Manage Inventory
            </a>
        @endcan
        @can('tenders.create', 'admin')
            <a href="{{ route('admin.tenders.create') }}" class="btn btn-secondary" style="font-size: 0.8125rem;">
                + New Tender
            </a>
        @endcan
    </div>
</div>

<!-- ==============================================================================
     1. TOP COMMERCIAL & OPERATIONAL KPIS
     ============================================================================== -->
<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.75rem;">
    <!-- Collected Revenue -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Collected Paid Revenue</div>
            <div class="stat-value" style="color: #10b981; font-size: 1.5rem;">₹{{ number_format($metrics['total_revenue'], 2) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">
                ₹{{ number_format($metrics['recent_paid_revenue'], 2) }} in last 30d
            </div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <svg style="width: 1.4rem; height: 1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
    </div>

    <!-- Average Paid Order Value -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Avg Paid Order Value</div>
            <div class="stat-value" style="font-size: 1.5rem;">₹{{ number_format($metrics['average_order_value'], 2) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">
                Based on {{ $metrics['paid_orders'] }} paid orders
            </div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
            <svg style="width: 1.4rem; height: 1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
        </div>
    </div>

    <!-- Storefront Orders -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Storefront Orders</div>
            <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($metrics['total_orders']) }}</div>
            <div style="font-size: 0.75rem; color: var(--primary-light); margin-top: 0.25rem;">
                {{ $metrics['paid_orders'] }} Paid &bull; {{ $metrics['pending_orders'] }} Pending
            </div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(2, 132, 199, 0.15); color: #38bdf8;">
            <svg style="width: 1.4rem; height: 1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
        </div>
    </div>

    <!-- Active Catalog Products -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Active Catalog Products</div>
            <div class="stat-value" style="font-size: 1.5rem;">{{ number_format($metrics['active_products']) }}</div>
            <div style="font-size: 0.75rem; color: {{ $metrics['out_of_stock_products'] > 0 ? '#ef4444' : 'var(--text-dim)' }}; margin-top: 0.25rem;">
                {{ $metrics['out_of_stock_products'] }} Out of Stock &bull; {{ $metrics['low_stock_products'] }} Low Stock
            </div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
            <svg style="width: 1.4rem; height: 1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        </div>
    </div>

    <!-- B2B Tenders Pipeline -->
    <div class="stat-card">
        <div>
            <div class="stat-label">Active Tenders (B2B)</div>
            <div class="stat-value" style="font-size: 1.5rem; color: #a78bfa;">{{ number_format($metrics['active_tenders']) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">
                {{ $metrics['total_tenders'] }} Total Tenders in System
            </div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(139, 92, 246, 0.15); color: #a78bfa;">
            <svg style="width: 1.4rem; height: 1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
    </div>
</div>

<!-- ==============================================================================
     2. ROW 1: CATALOG & INVENTORY ANALYTICS (2 COLUMNS)
     ============================================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; margin-bottom: 1.75rem;">

    <!-- CHART A: Products by Category -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Products by Category</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Distribution of {{ $metrics['active_products'] }} active retail products across categories</div>
            </div>
            <span class="badge badge-primary">{{ $categoriesWithCounts->count() }} Categories</span>
        </div>

        @if($categoriesWithCounts->isEmpty())
            <div class="empty-state" style="padding: 2rem 1rem;">
                <div class="empty-desc">No category product data available yet.</div>
            </div>
        @else
            @php
                $maxProductCount = max(1, $categoriesWithCounts->max('products_count'));
                $totalCategoryProducts = max(1, $categoriesWithCounts->sum('products_count'));
            @endphp
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                @foreach($categoriesWithCounts as $cat)
                    @php
                        $percentage = round(($cat->products_count / $totalCategoryProducts) * 100);
                        $barWidth = round(($cat->products_count / $maxProductCount) * 100);
                    @endphp
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8125rem; margin-bottom: 0.3rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-weight: 600; color: var(--text-main);">{{ $cat->name }}</span>
                                <span style="font-size: 0.7rem; color: var(--text-dim); font-family: monospace;">{{ $cat->slug }}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-weight: 700; color: var(--primary-light);">{{ $cat->products_count }}</span>
                                <span style="font-size: 0.75rem; color: var(--text-dim); width: 32px; text-align: right;">{{ $percentage }}%</span>
                            </div>
                        </div>
                        <div style="height: 8px; width: 100%; background: #0f172a; border-radius: 9999px; overflow: hidden; border: 1px solid var(--border-color);">
                            <div style="height: 100%; width: {{ $barWidth }}%; background: linear-gradient(90deg, #0284c7, #38bdf8); border-radius: 9999px; transition: width 0.3s ease;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-dim);">
                <span>Total Catalog: <strong>{{ $metrics['total_products'] }}</strong> items</span>
                <span>In Stock: <strong style="color: #10b981;">{{ $metrics['active_products'] - $metrics['out_of_stock_products'] }}</strong></span>
                <span>Low Stock: <strong style="color: #f59e0b;">{{ $metrics['low_stock_products'] }}</strong></span>
                <span>Out of Stock: <strong style="color: #ef4444;">{{ $metrics['out_of_stock_products'] }}</strong></span>
            </div>
        @endif
    </div>

    <!-- CHART B: Inventory Status & Warehouse Capacity -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Inventory & Warehouses</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Physical stock levels, reserved units, and facility breakdown</div>
            </div>
            <span class="badge badge-success">{{ number_format($metrics['total_inventory_qty']) }} Units On-Hand</span>
        </div>

        <!-- Inventory Status Distribution Bar -->
        <div style="margin-bottom: 1.25rem;">
            @php
                $totalInventoryRows = max(1, $metrics['inventory_in_stock'] + $metrics['inventory_low_stock'] + $metrics['inventory_out_of_stock']);
                $inStockPct = round(($metrics['inventory_in_stock'] / $totalInventoryRows) * 100);
                $lowStockPct = round(($metrics['inventory_low_stock'] / $totalInventoryRows) * 100);
                $outStockPct = max(0, 100 - $inStockPct - $lowStockPct);
            @endphp
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.4rem;">
                <span style="color: var(--text-muted);">Inventory SKU Health Distribution</span>
                <span style="color: var(--text-dim);">{{ $totalInventoryRows }} Variant Stock Records</span>
            </div>
            <div style="display: flex; height: 12px; width: 100%; border-radius: 9999px; overflow: hidden; background: #0f172a; border: 1px solid var(--border-color);">
                @if($metrics['inventory_in_stock'] > 0)
                    <div style="width: {{ $inStockPct }}%; background: #10b981;" title="In Stock: {{ $metrics['inventory_in_stock'] }} ({{ $inStockPct }}%)"></div>
                @endif
                @if($metrics['inventory_low_stock'] > 0)
                    <div style="width: {{ $lowStockPct }}%; background: #f59e0b;" title="Low Stock: {{ $metrics['inventory_low_stock'] }} ({{ $lowStockPct }}%)"></div>
                @endif
                @if($metrics['inventory_out_of_stock'] > 0)
                    <div style="width: {{ $outStockPct }}%; background: #ef4444;" title="Out of Stock: {{ $metrics['inventory_out_of_stock'] }} ({{ $outStockPct }}%)"></div>
                @endif
            </div>
            <div style="display: flex; gap: 1.25rem; font-size: 0.75rem; margin-top: 0.5rem; justify-content: center;">
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                    <span>In Stock ({{ $metrics['inventory_in_stock'] }})</span>
                </span>
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span>
                    <span>Low Stock ({{ $metrics['inventory_low_stock'] }})</span>
                </span>
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                    <span>Out of Stock ({{ $metrics['inventory_out_of_stock'] }})</span>
                </span>
            </div>
        </div>

        <!-- Warehouse Facilities Table -->
        <div style="border: 1px solid var(--border-color); border-radius: var(--radius); background: #162032; overflow: hidden; margin-bottom: 1rem;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase;">
                        <th style="padding: 0.5rem 0.75rem; text-align: left;">Facility / Warehouse</th>
                        <th style="padding: 0.5rem 0.75rem; text-align: right;">SKU Records</th>
                        <th style="padding: 0.5rem 0.75rem; text-align: right;">Physical Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $wh)
                        <tr style="border-bottom: 1px solid rgba(51, 65, 85, 0.5);">
                            <td style="padding: 0.5rem 0.75rem;">
                                <div style="font-weight: 600; color: var(--text-main);">{{ $wh->name }}</div>
                                <div style="font-size: 0.7rem; color: var(--text-dim); font-family: monospace;">Code: {{ $wh->code }}</div>
                            </td>
                            <td style="padding: 0.5rem 0.75rem; text-align: right; color: var(--text-muted);">
                                {{ $wh->inventories_count }}
                            </td>
                            <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 700; color: #38bdf8;">
                                {{ number_format($wh->inventories_sum_quantity ?? 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="padding: 1rem; text-align: center; color: var(--text-dim);">No warehouse facilities configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-dim);">
            <span>Committed / Reserved: <strong>{{ number_format($metrics['total_reserved_qty']) }}</strong> units</span>
            <span>Total Stock Movements: <strong>{{ number_format($metrics['stock_movements_count']) }}</strong></span>
            <span>Last 30 Days Movements: <strong>{{ number_format($metrics['recent_movements_count']) }}</strong></span>
        </div>
    </div>
</div>

<!-- ==============================================================================
     3. ROW 2: SALES, PAYMENTS & TENDERS ANALYTICS (3 COLUMNS)
     ============================================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.75rem;">

    <!-- CHART C: Orders Overview -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Orders Overview</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Storefront order pipeline by status</div>
            </div>
            <span class="badge badge-primary">{{ $metrics['total_orders'] }} Total</span>
        </div>

        @if($metrics['total_orders'] === 0)
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <div class="empty-title" style="font-size: 0.9375rem;">No customer orders placed yet</div>
                <div class="empty-desc" style="font-size: 0.8125rem;">Live orders will populate this breakdown in real time.</div>
            </div>
        @else
            @php
                $totOrders = max(1, $metrics['total_orders']);
                $orderStatusMeta = [
                    'pending' => ['label' => 'Pending', 'color' => '#f59e0b'],
                    'confirmed' => ['label' => 'Confirmed', 'color' => '#38bdf8'],
                    'processing' => ['label' => 'Processing', 'color' => '#0284c7'],
                    'shipped' => ['label' => 'Shipped', 'color' => '#818cf8'],
                    'delivered' => ['label' => 'Delivered', 'color' => '#10b981'],
                    'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444'],
                ];
            @endphp
            <!-- Segmented distribution bar -->
            <div style="display: flex; height: 10px; width: 100%; border-radius: 9999px; overflow: hidden; background: #0f172a; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                @foreach($orderStatusMeta as $key => $meta)
                    @php $cnt = $orderStatuses[$key] ?? 0; @endphp
                    @if($cnt > 0)
                        <div style="width: {{ round(($cnt / $totOrders) * 100) }}%; background: {{ $meta['color'] }};" title="{{ $meta['label'] }}: {{ $cnt }}"></div>
                    @endif
                @endforeach
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem;">
                @foreach($orderStatusMeta as $key => $meta)
                    @php
                        $cnt = $orderStatuses[$key] ?? 0;
                        $pct = round(($cnt / $totOrders) * 100);
                    @endphp
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.5rem; border-radius: var(--radius); background: rgba(15, 23, 42, 0.4);">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $meta['color'] }};"></span>
                            <span style="color: var(--text-main);">{{ $meta['label'] }}</span>
                        </span>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="font-weight: 700; color: var(--text-main);">{{ $cnt }}</span>
                            <span style="color: var(--text-dim); font-size: 0.75rem; width: 30px; text-align: right;">{{ $pct }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- CHART D: Payment Analytics -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Payment Status</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Razorpay & customer gateway transactions</div>
            </div>
            @php $totalTxCount = array_sum(array_column($paymentSummary, 'count')); @endphp
            <span class="badge {{ $totalTxCount > 0 ? 'badge-success' : 'badge-neutral' }}">{{ $totalTxCount }} Transactions</span>
        </div>

        @if($totalTxCount === 0)
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <div class="empty-title" style="font-size: 0.9375rem;">No payment transactions recorded yet</div>
                <div class="empty-desc" style="font-size: 0.8125rem;">Payment status transactions will update automatically upon checkout.</div>
            </div>
        @else
            @php
                $totTx = max(1, $totalTxCount);
                $paymentStatusMeta = [
                    'paid' => ['label' => 'Paid (Settled)', 'color' => '#10b981', 'badge' => 'badge-success'],
                    'pending' => ['label' => 'Pending', 'color' => '#f59e0b', 'badge' => 'badge-warning'],
                    'failed' => ['label' => 'Failed', 'color' => '#ef4444', 'badge' => 'badge-danger'],
                    'cancelled' => ['label' => 'Cancelled', 'color' => '#64748b', 'badge' => 'badge-neutral'],
                    'refunded' => ['label' => 'Refunded', 'color' => '#a78bfa', 'badge' => 'badge-neutral'],
                ];
            @endphp

            <!-- Segmented transaction bar -->
            <div style="display: flex; height: 10px; width: 100%; border-radius: 9999px; overflow: hidden; background: #0f172a; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                @foreach($paymentStatusMeta as $statusKey => $meta)
                    @php $cnt = $paymentSummary[$statusKey]['count'] ?? 0; @endphp
                    @if($cnt > 0)
                        <div style="width: {{ round(($cnt / $totTx) * 100) }}%; background: {{ $meta['color'] }};" title="{{ $meta['label'] }}: {{ $cnt }}"></div>
                    @endif
                @endforeach
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem;">
                @foreach($paymentStatusMeta as $statusKey => $meta)
                    @php
                        $data = $paymentSummary[$statusKey];
                        $cnt = $data['count'];
                        $amt = $data['total'];
                    @endphp
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.5rem; border-radius: var(--radius); background: rgba(15, 23, 42, 0.4);">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $meta['color'] }};"></span>
                            <span style="color: var(--text-main);">{{ $meta['label'] }}</span>
                        </span>
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <span style="font-weight: 700; color: var(--text-main);">{{ $cnt }}</span>
                            <span style="font-size: 0.75rem; font-family: monospace; color: {{ $statusKey === 'paid' ? '#10b981' : 'var(--text-dim)' }};">
                                ₹{{ number_format($amt, 2) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- CHART E: Tender Pipeline Status -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Tender Status</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Institutional contracts & B2B procurement</div>
            </div>
            <span class="badge badge-primary">{{ $metrics['total_tenders'] }} Tenders</span>
        </div>

        @if($metrics['total_tenders'] === 0)
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <div class="empty-title" style="font-size: 0.9375rem;">No tender records created yet</div>
                <div class="empty-desc" style="font-size: 0.8125rem;">Create tenders via the Tender Management module.</div>
            </div>
        @else
            @php
                $totTenders = max(1, $metrics['total_tenders']);
                $tenderMeta = [
                    'draft' => ['label' => 'Draft', 'color' => '#64748b'],
                    'active' => ['label' => 'Active', 'color' => '#10b981'],
                    'completed' => ['label' => 'Completed', 'color' => '#0284c7'],
                    'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444'],
                    'archived' => ['label' => 'Archived', 'color' => '#475569'],
                ];
            @endphp

            <!-- Segmented tender bar -->
            <div style="display: flex; height: 10px; width: 100%; border-radius: 9999px; overflow: hidden; background: #0f172a; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                @foreach($tenderMeta as $tKey => $meta)
                    @php $tCnt = $tenderStatuses[$tKey] ?? 0; @endphp
                    @if($tCnt > 0)
                        <div style="width: {{ round(($tCnt / $totTenders) * 100) }}%; background: {{ $meta['color'] }};" title="{{ $meta['label'] }}: {{ $tCnt }}"></div>
                    @endif
                @endforeach
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem;">
                @foreach($tenderMeta as $tKey => $meta)
                    @php
                        $tCnt = $tenderStatuses[$tKey] ?? 0;
                        $tPct = round(($tCnt / $totTenders) * 100);
                    @endphp
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.5rem; border-radius: var(--radius); background: rgba(15, 23, 42, 0.4);">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $meta['color'] }};"></span>
                            <span style="color: var(--text-main);">{{ $meta['label'] }}</span>
                        </span>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="font-weight: 700; color: var(--text-main);">{{ $tCnt }}</span>
                            <span style="color: var(--text-dim); font-size: 0.75rem; width: 30px; text-align: right;">{{ $tPct }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- ==============================================================================
     4. ROW 3: FULFILLMENT & LOGISTICS + RETURNS & REFUNDS (2 COLUMNS)
     ============================================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; margin-bottom: 1.75rem;">

    <!-- Shipping & Logistics -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Shipping & Logistics (Delhi NCR)</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Order fulfillment status and dispatch activity</div>
            </div>
            <span class="badge badge-neutral">3-Day SLA Delivery</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <div style="padding: 1rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Unfulfilled</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-top: 0.2rem;">{{ $shippingStatuses['unfulfilled'] }}</div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">Awaiting warehouse pack</div>
            </div>

            <div style="padding: 1rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">In-Transit / Partial</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #38bdf8; margin-top: 0.2rem;">{{ $shippingStatuses['partially_fulfilled'] }}</div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">Out for delivery in Delhi NCR</div>
            </div>

            <div style="padding: 1rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Fulfilled / Delivered</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981; margin-top: 0.2rem;">{{ $shippingStatuses['fulfilled'] }}</div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">Completed deliveries</div>
            </div>

            <div style="padding: 1rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Return Shipments</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #f87171; margin-top: 0.2rem;">{{ $shippingStatuses['returned'] }}</div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">Inbound return transit</div>
            </div>
        </div>
    </div>

    <!-- Returns & Refunds -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Returns & Refunds</h2>
                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.2rem;">Customer return requests & processed refunds</div>
            </div>
            <span class="badge {{ $returnStatuses['requested'] > 0 ? 'badge-warning' : 'badge-neutral' }}">
                {{ $returnStatuses['requested'] }} Action Required
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
            <div style="padding: 0.85rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim);">Requested</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #f59e0b; margin-top: 0.15rem;">{{ $returnStatuses['requested'] }}</div>
            </div>

            <div style="padding: 0.85rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim);">Approved</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #38bdf8; margin-top: 0.15rem;">{{ $returnStatuses['approved'] }}</div>
            </div>

            <div style="padding: 0.85rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim);">Completed</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #10b981; margin-top: 0.15rem;">{{ $returnStatuses['completed'] }}</div>
            </div>

            <div style="padding: 0.85rem; background: #162032; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="font-size: 0.75rem; color: var(--text-dim);">Rejected</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #ef4444; margin-top: 0.15rem;">{{ $returnStatuses['rejected'] }}</div>
            </div>
        </div>

        <div style="padding: 0.75rem 1rem; background: rgba(15, 23, 42, 0.6); border-radius: var(--radius); display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-color);">
            <span style="font-size: 0.8125rem; color: var(--text-muted);">Total Settled Refunds:</span>
            <span style="font-weight: 700; color: #f87171; font-size: 0.9375rem; font-family: monospace;">₹{{ number_format($metrics['total_refunds_value'], 2) }}</span>
        </div>
    </div>
</div>

<!-- ==============================================================================
     5. ROW 4: RECENT OPERATIONAL ACTIVITY (2 COLUMNS)
     ============================================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem;">
    <!-- Recent Customer Orders -->
    <div class="card" style="margin-bottom: 0; overflow: hidden;">
        <div class="card-header">
            <h2 class="card-title">Recent Customer Orders</h2>
            @can('orders.view', 'admin')
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">View All Orders &rarr;</a>
            @endcan
        </div>

        @if($recentOrders->isEmpty())
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <div class="empty-desc">No customer orders placed yet.</div>
            </div>
        @else
            <div class="table-container" style="border: none; border-radius: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th style="text-align: right;">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $ro)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $ro) }}" style="font-weight: 600; color: var(--primary-light); text-decoration: none;">
                                        #{{ $ro->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <span style="font-weight: 500; font-size: 0.8125rem;">
                                        {{ $ro->customer->name ?? ($ro->shipping_address['name'] ?? 'Guest') }}
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 600; font-size: 0.8125rem; font-family: monospace;">
                                    ₹{{ number_format($ro->grand_total, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $ro->status->value === 'delivered' ? 'badge-success' : ($ro->status->value === 'cancelled' ? 'badge-danger' : 'badge-primary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $ro->status->value)) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Recent Security Audit Events -->
    <div class="card" style="margin-bottom: 0; overflow: hidden;">
        <div class="card-header">
            <h2 class="card-title">Recent Audit Events</h2>
            @can('audit-logs.view', 'admin')
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary btn-sm">View Audit Log &rarr;</a>
            @endcan
        </div>

        @if($recentActivity->isEmpty())
            <div class="empty-state" style="padding: 2.5rem 1rem;">
                <div class="empty-desc">No security audit events recorded yet.</div>
            </div>
        @else
            <div class="table-container" style="border: none; border-radius: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Actor</th>
                            <th style="text-align: right;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivity as $log)
                            <tr>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.75rem; font-weight: 600; color: var(--primary-light);">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td style="font-size: 0.8125rem;">
                                    {{ $log->admin?->name ?? ($log->customer?->name ?? 'System') }}
                                </td>
                                <td style="text-align: right; font-size: 0.75rem; color: var(--text-dim);">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
