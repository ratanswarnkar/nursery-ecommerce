<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Nursery E-Commerce</title>
    <style>
        :root {
            --bg-color: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #16a34a;
            --primary-hover: #15803d;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg-color); color: var(--text-main); line-height: 1.5; padding: 2rem 1rem; }
        .container { max-width: 1080px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-title { font-size: 1.75rem; font-weight: 700; color: #166534; display: flex; align-items: center; gap: 0.75rem; }
        .auth-bar { font-size: 0.875rem; color: var(--text-muted); }
        .auth-bar a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .notice-banner { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; }
        .warning-banner { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 0.875rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .layout-grid { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start; }
        @media (max-width: 860px) { .layout-grid { grid-template-columns: 1fr; } }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05); overflow: hidden; }
        .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); font-weight: 600; }
        .cart-item { display: grid; grid-template-columns: 1fr auto auto auto; gap: 1.25rem; padding: 1.25rem; border-bottom: 1px solid var(--border); align-items: center; }
        .cart-item:last-child { border-bottom: none; }
        @media (max-width: 640px) { .cart-item { grid-template-columns: 1fr; gap: 0.75rem; } }
        .item-info-title { font-weight: 600; font-size: 1rem; color: var(--text-main); }
        .item-info-meta { font-size: 0.8125rem; color: var(--text-muted); font-family: monospace; margin-top: 0.25rem; }
        .item-stock-msg { font-size: 0.8125rem; margin-top: 0.35rem; font-weight: 500; }
        .stock-in { color: #16a34a; }
        .stock-warn { color: #d97706; }
        .stock-err { color: #dc2626; }
        .qty-form { display: flex; align-items: center; gap: 0.5rem; }
        .qty-input { width: 64px; padding: 0.4rem 0.5rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem; text-align: center; }
        .btn-qty { padding: 0.4rem 0.6rem; background: #f1f5f9; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; color: var(--text-main); }
        .btn-qty:hover { background: #e2e8f0; }
        .price-col { text-align: right; min-width: 90px; }
        .unit-price { font-size: 0.8125rem; color: var(--text-muted); }
        .line-total { font-weight: 700; font-size: 1.0625rem; color: var(--text-main); }
        .btn-remove { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.4rem; border-radius: 0.375rem; }
        .btn-remove:hover { color: var(--danger); background: #fee2e2; }
        .summary-card { padding: 1.5rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.9375rem; }
        .summary-total { display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--border); font-size: 1.25rem; font-weight: 700; color: #166534; }
        .btn-clear { width: 100%; padding: 0.6rem; background: transparent; border: 1px solid var(--border); border-radius: 0.375rem; color: var(--text-muted); font-size: 0.875rem; cursor: pointer; margin-top: 1rem; }
        .btn-clear:hover { background: #fef2f2; color: var(--danger); border-color: #fecaca; }
        .empty-cart { padding: 4rem 2rem; text-align: center; }
        .empty-icon { width: 64px; height: 64px; margin: 0 auto 1.5rem; color: #94a3b8; }
        .empty-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .empty-desc { color: var(--text-muted); font-size: 0.9375rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="header-title">
            <svg style="width: 2rem; height: 2rem; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <span>Plant Nursery Cart</span>
        </h1>
        <div class="auth-bar">
            @if(auth('customer')->check())
                <span>Signed in as <strong>{{ auth('customer')->user()->phone }}</strong></span>
            @else
                <span>Guest Session &bull; <a href="{{ route('customer.login') }}">Log in to save cart</a></span>
            @endif
        </div>
    </div>

    <!-- Live Inventory Notice -->
    <div class="notice-banner">
        <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Stock is validated in real time. Items in cart are not reserved until Phase 5 checkout.</span>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if($has_issues)
        <div class="warning-banner">
            <strong>Stock Alert:</strong> One or more items in your cart currently have limited or unavailable inventory. Please adjust quantities or remove unavailable items before ordering.
        </div>
    @endif

    @if(empty($items))
        <div class="card empty-cart">
            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <div class="empty-title">Your cart is empty</div>
            <div class="empty-desc">Discover our botanical collection, healthy saplings, and nursery tools.</div>
        </div>
    @else
        <div class="layout-grid">
            <div class="card">
                <div class="card-header">
                    <span>Cart Items ({{ $item_count }} {{ Str::plural('unit', $item_count) }})</span>
                </div>

                @foreach($items as $entry)
                    @php
                        $item = $entry['cart_item'];
                        $variant = $entry['variant'];
                        $product = $entry['product'];
                    @endphp
                    <div class="cart-item" style="{{ ! $entry['is_purchasable'] ? 'background: #fff8f8;' : '' }}">
                        <div>
                            <div class="item-info-title">{{ $product?->name ?? 'Unavailable Item' }}</div>
                            <div class="item-info-meta">
                                SKU: {{ $variant?->sku ?? '—' }}
                                @if($variant && $variant->attributeValues->isNotEmpty())
                                    | {{ $variant->attributeValues->pluck('value')->implode(' / ') }}
                                @endif
                            </div>

                            @if($entry['status'] === 'in_stock')
                                <div class="item-stock-msg stock-in">&check; In Stock ({{ $entry['available_stock'] }} available)</div>
                            @elseif($entry['status'] === 'insufficient_stock')
                                <div class="item-stock-msg stock-warn">&excl; {{ $entry['message'] }}</div>
                            @elseif($entry['status'] === 'out_of_stock')
                                <div class="item-stock-msg stock-err">&cross; Out of Stock</div>
                            @else
                                <div class="item-stock-msg stock-err">&cross; {{ $entry['message'] ?? 'Unavailable' }}</div>
                            @endif
                        </div>

                        <div>
                            <form method="POST" action="{{ route('cart.items.update', $item) }}" class="qty-form">
                                @csrf
                                @method('PUT')
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="50" class="qty-input">
                                <button type="submit" class="btn-qty">Update</button>
                            </form>
                        </div>

                        <div class="price-col">
                            <div class="unit-price">₹{{ number_format((float) $entry['unit_price'], 2) }} each</div>
                            <div class="line-total">₹{{ number_format((float) $entry['line_total'], 2) }}</div>
                        </div>

                        <div>
                            <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-remove" title="Remove item">
                                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                <div class="card summary-card">
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.25rem;">Order Summary</h2>
                    <div class="summary-row">
                        <span style="color: var(--text-muted);">Subtotal</span>
                        <span style="font-weight: 600;">₹{{ number_format((float) $subtotal, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span style="color: var(--text-muted);">Estimated Tax</span>
                        <span style="color: var(--text-muted); font-size: 0.8125rem;">Calculated at checkout</span>
                    </div>
                    <div class="summary-total">
                        <span>Total (Est.)</span>
                        <span>₹{{ number_format((float) $subtotal, 2) }}</span>
                    </div>

                    <div style="margin-top: 1.5rem; padding: 0.75rem; background: #f8fafc; border: 1px dashed var(--border); border-radius: 0.5rem; font-size: 0.8125rem; color: var(--text-muted); text-align: center;">
                        Checkout and payment processing will be available in Phase 5.
                    </div>

                    <form method="POST" action="{{ route('cart.clear') }}">
                        @csrf
                        <button type="submit" class="btn-clear" onclick="return confirm('Clear all items from your cart?')">
                            Clear Entire Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
</body>
</html>
