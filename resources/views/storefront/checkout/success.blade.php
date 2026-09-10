@extends('layouts.storefront')

@section('seo')
    <title>Order Confirmed #{{ $order->order_number }} | Sugandha Farms and Nursery</title>
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    {{-- Success Hero Banner --}}
    <div class="text-center space-y-4 mb-10 sm:mb-12">
        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center mx-auto shadow-inner">
            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <div class="space-y-1">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                Order Received
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">
                Thank You, {{ $order->customer_name ?? $customer->name }}!
            </h1>
            <p class="text-sm text-stone-600 max-w-md mx-auto">
                Your plant order has been placed successfully. A receipt snapshot has been saved to your account.
            </p>
        </div>

        {{-- Order Number Tag --}}
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-stone-100 border border-stone-200 text-xs font-mono text-stone-800">
            <span>Order Number:</span>
            <span class="font-bold text-emerald-950 font-mono text-sm">#{{ $order->order_number }}</span>
        </div>
    </div>

    {{-- Order Status Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm space-y-1">
            <span class="text-xs text-stone-500 font-semibold uppercase tracking-wider">Order Status</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span class="text-base font-bold text-stone-900 capitalize">{{ $order->status->value }}</span>
            </div>
            <p class="text-xs text-stone-500">Order recorded and awaiting greenhouse dispatch verification.</p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm space-y-1">
            <span class="text-xs text-stone-500 font-semibold uppercase tracking-wider">Payment Status</span>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-stone-400"></span>
                <span class="text-base font-bold text-stone-900 capitalize">{{ $order->payment_status->value }}</span>
            </div>
            <p class="text-xs text-stone-500">Payment verification and status updates are tracked in your customer account.</p>
        </div>
    </div>

    {{-- Order Items Table Card --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-6 mb-8">
        <h2 class="text-base sm:text-lg font-bold text-stone-900 border-b border-stone-100 pb-3">Items in This Order</h2>

        <div class="divide-y divide-stone-100">
            @foreach($order->items as $item)
                <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                    <div class="space-y-1 min-w-0 flex-grow">
                        <h3 class="text-sm font-bold text-stone-900">{{ $item->product_name }}</h3>
                        <div class="text-xs text-stone-500 font-mono">
                            SKU: {{ $item->sku }} &bull; {{ $item->variant_name }}
                        </div>
                    </div>

                    <div class="text-xs sm:text-sm text-stone-600 text-right shrink-0">
                        <span>{{ $item->quantity }} &times; ₹{{ number_format((float) $item->price, 2) }}</span>
                        <div class="font-extrabold text-stone-900 text-sm sm:text-base">
                            ₹{{ number_format((float) $item->subtotal, 2) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Financial Breakdown Summary --}}
        <div class="pt-4 border-t border-stone-200 space-y-2.5 text-xs sm:text-sm">
            <div class="flex items-center justify-between text-stone-600">
                <span>Subtotal</span>
                <span class="font-semibold text-stone-900">₹{{ number_format((float) $order->subtotal, 2) }}</span>
            </div>

            <div class="flex items-center justify-between text-stone-600">
                <span>Tax</span>
                <span class="font-semibold text-stone-900">₹{{ number_format((float) $order->tax_amount, 2) }}</span>
            </div>

            <div class="flex items-center justify-between text-stone-600">
                <span>Shipping / Delivery</span>
                <span class="font-semibold text-stone-900">₹{{ number_format((float) $order->shipping_amount, 2) }}</span>
            </div>

            @if(bccomp((string) $order->discount_amount, '0.00', 2) > 0)
                <div class="flex items-center justify-between text-emerald-700">
                    <span>Discount</span>
                    <span class="font-semibold">-₹{{ number_format((float) $order->discount_amount, 2) }}</span>
                </div>
            @endif

            <div class="pt-3 border-t border-stone-200 flex items-baseline justify-between">
                <span class="font-bold text-stone-900 text-base">Grand Total</span>
                <span class="text-2xl font-black text-emerald-950 tracking-tight">₹{{ number_format((float) $order->grand_total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Address Snapshots Card --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        {{-- Shipping Address Snapshot --}}
        <div class="bg-white rounded-3xl p-6 border border-stone-200/80 shadow-sm space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500">Shipping Address Snapshot</h3>
            @php $shipAddr = $order->shipping_address_json; @endphp
            <div class="text-xs text-stone-700 space-y-0.5 leading-relaxed">
                <p class="font-bold text-stone-900 text-sm">{{ $shipAddr['recipient_name'] ?? '—' }}</p>
                <p class="text-stone-500 font-mono">{{ $shipAddr['phone'] ?? '—' }}</p>
                <p>{{ $shipAddr['address_line_1'] ?? '' }}@if(!empty($shipAddr['address_line_2'])), {{ $shipAddr['address_line_2'] }}@endif</p>
                <p>{{ $shipAddr['city'] ?? '' }}, {{ $shipAddr['state'] ?? '' }} - <span class="font-mono">{{ $shipAddr['postal_code'] ?? '' }}</span></p>
                <p class="text-stone-500">{{ $shipAddr['country'] ?? 'India' }}</p>
            </div>
        </div>

        {{-- Billing Address Snapshot --}}
        <div class="bg-white rounded-3xl p-6 border border-stone-200/80 shadow-sm space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500">Billing Address Snapshot</h3>
            @php $billAddr = $order->billing_address_json; @endphp
            <div class="text-xs text-stone-700 space-y-0.5 leading-relaxed">
                <p class="font-bold text-stone-900 text-sm">{{ $billAddr['recipient_name'] ?? '—' }}</p>
                <p class="text-stone-500 font-mono">{{ $billAddr['phone'] ?? '—' }}</p>
                <p>{{ $billAddr['address_line_1'] ?? '' }}@if(!empty($billAddr['address_line_2'])), {{ $billAddr['address_line_2'] }}@endif</p>
                <p>{{ $billAddr['city'] ?? '' }}, {{ $billAddr['state'] ?? '' }} - <span class="font-mono">{{ $billAddr['postal_code'] ?? '' }}</span></p>
                <p class="text-stone-500">{{ $billAddr['country'] ?? 'India' }}</p>
            </div>
        </div>
    </div>

    {{-- Action CTAs --}}
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="{{ route('account.orders.index') }}"
           class="w-full sm:w-auto py-3.5 px-6 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white text-xs sm:text-sm font-bold shadow-sm text-center transition-all">
            View All Orders in Account
        </a>
        <a href="{{ route('shop.index') }}"
           class="w-full sm:w-auto py-3.5 px-6 rounded-2xl bg-stone-100 hover:bg-stone-200 text-stone-800 text-xs sm:text-sm font-semibold text-center transition-colors">
            Continue Shopping
        </a>
    </div>
</div>
@endsection
