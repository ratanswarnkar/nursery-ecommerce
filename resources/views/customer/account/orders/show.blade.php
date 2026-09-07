@extends('layouts.storefront')

@section('seo')
    <title>Order #{{ $order->order_number }} | Sugandha Farms and Nursery</title>
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Account', 'url' => route('account.dashboard')],
        ['name' => 'Orders', 'url' => route('account.orders.index')],
        ['name' => '#' . $order->order_number, 'url' => '']
    ]" />

    {{-- Order Header --}}
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-stone-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight font-mono">
                    #{{ $order->order_number }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                    {{ $order->status->value }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-stone-100 text-stone-700">
                    Payment: {{ $order->payment_status->value }}
                </span>
            </div>
            <p class="text-xs text-stone-500">
                Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}
            </p>
        </div>

        <div class="text-left sm:text-right">
            <span class="text-[10px] uppercase font-bold text-stone-400 block">Total Amount</span>
            <span class="text-2xl font-black text-emerald-950">₹{{ number_format((float) $order->grand_total, 2) }}</span>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-6">
        <h2 class="text-base sm:text-lg font-bold text-stone-900 border-b border-stone-100 pb-3">Items in Order</h2>

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

        {{-- Financial Summary --}}
        <div class="pt-4 border-t border-stone-200 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between text-stone-600">
                <span>Subtotal</span>
                <span class="font-semibold text-stone-900">₹{{ number_format((float) $order->subtotal, 2) }}</span>
            </div>
            <div class="flex items-center justify-between text-stone-600">
                <span>Tax</span>
                <span class="font-semibold text-stone-900">₹{{ number_format((float) $order->tax_amount, 2) }}</span>
            </div>
            <div class="flex items-center justify-between text-stone-600">
                <span>Shipping</span>
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
                <span class="text-2xl font-black text-emerald-950">₹{{ number_format((float) $order->grand_total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Snapshots Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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

    {{-- Status History Timeline --}}
    @if($order->statusHistories->isNotEmpty())
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-4">
            <h3 class="text-base font-bold text-stone-900">Order Timeline</h3>
            <div class="space-y-3">
                @foreach($order->statusHistories as $history)
                    <div class="flex items-start gap-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-emerald-700 mt-1.5 shrink-0"></div>
                        <div class="flex-grow min-w-0">
                            <span class="font-bold text-stone-900 capitalize">{{ $history->to_status }}</span>
                            @if($history->comment)
                                <span class="text-stone-600">&bull; {{ $history->comment }}</span>
                            @endif
                            <span class="text-stone-400 block text-[11px] font-mono mt-0.5">{{ $history->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="pt-2">
        <a href="{{ route('account.orders.index') }}" class="text-xs font-semibold text-emerald-800 hover:text-emerald-950 flex items-center gap-1">
            &larr; Back to all orders
        </a>
    </div>
</div>
@endsection
