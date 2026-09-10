@extends('layouts.storefront')

@section('seo')
    <title>Your Botanical Cart | Sugandha Farms and Nursery</title>
    <meta name="description" content="Review your selected nursery plants, saplings, and garden supplies.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="space-y-8">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Shop', 'url' => route('shop.index')],
        ['name' => 'Shopping Cart', 'url' => '']
    ]" />

    <div class="flex items-baseline justify-between border-b border-stone-200 pb-4">
        <div>
            <h1 class="text-3xl font-extrabold text-stone-900 tracking-tight">Shopping Cart</h1>
            <p class="text-xs text-stone-500 mt-1">
                @if(auth('customer')->check())
                    Saved under customer session <span class="font-semibold text-emerald-800">{{ auth('customer')->user()->phone }}</span>
                @else
                    Guest Session &bull; <a href="{{ route('customer.login') }}" class="font-semibold text-emerald-700 hover:underline">Log in to sync across devices</a>
                @endif
            </p>
        </div>

        @if(!empty($items))
            <form method="POST" action="{{ route('cart.clear') }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Are you sure you want to clear all items from your cart?')"
                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition-colors">
                    Clear Entire Cart
                </button>
            </form>
        @endif
    </div>

    {{-- Unreserved Live Stock Notice (Step 8 Mandatory) --}}
    <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs sm:text-sm flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <span class="font-bold">Live Inventory Notice:</span> Items in cart are not reserved and stock is confirmed again during checkout.
        </div>
    </div>

    {{-- Warning banner if items have issues --}}
    @if($has_issues)
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <span class="font-bold">Stock Alert:</span> One or more botanical items in your cart currently have limited or unavailable inventory. Please adjust quantities or remove unavailable items before ordering.
            </div>
        </div>
    @endif

    @if(empty($items))
        <div class="p-16 text-center bg-white rounded-3xl border border-stone-200/80 shadow-sm space-y-4">
            <div class="w-20 h-20 mx-auto rounded-full bg-emerald-50 text-emerald-800 flex items-center justify-center">
                <svg class="w-10 h-10 stroke-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-stone-900">Your cart is empty</h2>
            <p class="text-sm text-stone-500 max-w-md mx-auto">
                Explore our lush indoor foliage, blooming saplings, handcrafted terracotta planters, and specialized fertilizers.
            </p>
            <div class="pt-4">
                <a href="{{ route('shop.index') }}"
                   class="inline-flex items-center px-6 py-3 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm shadow-md transition-colors">
                    Explore Botanical Catalog
                </a>
            </div>
        </div>
    @else
        {{-- Delivery Progress Indicator (Scope A & G) --}}
        <x-storefront.free-delivery-progress :subtotal="$subtotal" />

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            {{-- Cart Items List (Cols 1-8) --}}
            <div class="lg:col-span-8 bg-white rounded-3xl border border-stone-200/80 shadow-sm overflow-hidden divide-y divide-stone-100">
                <div class="p-4 sm:p-5 bg-stone-50/70 border-b border-stone-200/80 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-stone-600">
                    <span>Cart Items ({{ $item_count }} {{ Str::plural('unit', $item_count) }})</span>
                    <span>Subtotal</span>
                </div>

                @foreach($items as $entry)
                    @php
                        $item = $entry['cart_item'];
                        $variant = $entry['variant'];
                        $product = $entry['product'];
                        $primaryImage = $product?->primaryImage ?? $product?->images?->first();
                        $imageUrl = $primaryImage?->url;
                    @endphp

                    <div class="p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 {{ ! $entry['is_purchasable'] ? 'bg-rose-50/40' : '' }}">
                        {{-- Product Thumbnail & Title/Meta --}}
                        <div class="flex items-start gap-4 flex-grow min-w-0">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-stone-100 overflow-hidden border border-stone-200 shrink-0">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $product?->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-stone-300">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-1 min-w-0 flex-grow">
                                @if($product)
                                    <h3 class="font-bold text-stone-900 text-sm sm:text-base leading-snug hover:text-emerald-800 transition-colors truncate">
                                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                    </h3>
                                @else
                                    <h3 class="font-bold text-stone-500 text-sm">Unavailable Product</h3>
                                @endif

                                <div class="text-xs text-stone-500 font-mono">
                                    SKU: {{ $variant?->sku ?? '—' }}
                                    @if($variant && $variant->attributeValues->isNotEmpty())
                                        &bull; {{ $variant->attributeValues->pluck('value')->implode(' / ') }}
                                    @endif
                                </div>

                                {{-- Stock Status Messaging --}}
                                @if($entry['status'] === 'in_stock')
                                    <div class="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        In Stock ({{ $entry['available_stock'] }} available)
                                    </div>
                                @elseif($entry['status'] === 'insufficient_stock')
                                    <div class="text-xs font-semibold text-amber-700 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ $entry['message'] }}
                                    </div>
                                @elseif($entry['status'] === 'out_of_stock')
                                    <div class="text-xs font-semibold text-rose-700 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Out of Stock
                                    </div>
                                @else
                                    <div class="text-xs font-semibold text-rose-700 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        {{ $entry['message'] ?? 'Unavailable' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Quantity Form & Price Controls --}}
                        <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-stone-100">
                            {{-- Quantity Update Form --}}
                            <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="number"
                                       name="quantity"
                                       value="{{ $item->quantity }}"
                                       min="1"
                                       max="50"
                                       class="w-16 px-2.5 py-1.5 text-center text-xs font-semibold rounded-xl border border-stone-300 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                                <button type="submit"
                                        class="px-2.5 py-1.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold transition-colors">
                                    Update
                                </button>
                            </form>

                            {{-- Price & Line Total --}}
                            <div class="text-right min-w-[90px]">
                                <div class="text-xs text-stone-400">₹{{ number_format((float) $entry['unit_price'], 2) }}</div>
                                <div class="text-base font-bold text-stone-900 tracking-tight">₹{{ number_format((float) $entry['line_total'], 2) }}</div>
                            </div>

                            {{-- Remove Item --}}
                            <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        title="Remove item"
                                        class="p-2 rounded-xl text-stone-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Summary Card (Cols 9-12) --}}
            <div class="lg:col-span-4 bg-white rounded-3xl p-6 border border-stone-200/80 shadow-sm space-y-6 sticky top-24">
                <h2 class="text-lg font-bold text-stone-900 border-b border-stone-100 pb-3">Cart Summary</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between text-stone-600">
                        <span>Items Subtotal</span>
                        <span class="font-semibold text-stone-900">₹{{ number_format((float) $subtotal, 2) }}</span>
                    </div>

                    <div class="flex items-start justify-between text-stone-600">
                        <div>
                            <span>Estimated Shipping</span>
                            <span class="block text-[11px] text-stone-500">
                                @if(bccomp((string) $subtotal, '1000.00', 2) > 0)
                                    Orders ABOVE ₹1,000 qualify for FREE delivery
                                @else
                                    Free delivery on orders ABOVE ₹1,000
                                @endif
                            </span>
                        </div>
                        <div class="text-right">
                            @if(bccomp((string) $subtotal, '1000.00', 2) > 0)
                                <span class="font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded text-xs">FREE</span>
                            @else
                                <span class="text-xs font-semibold text-stone-600">Calculated at checkout</span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-3 border-t border-stone-200 flex items-baseline justify-between">
                        <span class="font-bold text-stone-900">Estimated Total</span>
                        <span class="text-2xl font-extrabold text-emerald-950 tracking-tight">₹{{ number_format((float) $subtotal, 2) }}</span>
                    </div>
                </div>

                {{-- Delivery Transparency Badge --}}
                <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-200 text-xs text-stone-600 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-stone-800">
                        <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Delhi NCR Delivery Only</span>
                    </div>
                    <p class="text-[11px] text-stone-500 leading-relaxed">Delivered within 3 days directly from our nursery greenhouse.</p>
                </div>

                @if($has_issues)
                    <button type="button" disabled
                            class="w-full py-3.5 px-5 rounded-2xl bg-stone-200 text-stone-400 font-bold text-sm cursor-not-allowed text-center">
                        Resolve Alerts to Checkout
                    </button>
                @else
                    <a href="{{ route('checkout.index') }}"
                       class="w-full py-3.5 px-5 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/15 transition-all hover:scale-[1.01] active:scale-[0.99]">
                        <span>Proceed to Checkout</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                @endif

                <a href="{{ route('shop.index') }}"
                   class="block w-full py-3 px-4 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-800 text-center font-semibold text-xs transition-colors">
                    Continue Browsing Plants
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
