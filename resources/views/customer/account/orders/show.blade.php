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

    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Order Header --}}
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-stone-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight font-mono">
                    #{{ $order->order_number }}
                </h1>
                @php
                    $statusBadge = match($order->status) {
                        \App\Enums\OrderStatus::DELIVERED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        \App\Enums\OrderStatus::SHIPPED, \App\Enums\OrderStatus::OUT_FOR_DELIVERY => 'bg-blue-50 text-blue-800 border-blue-200',
                        \App\Enums\OrderStatus::PROCESSING, \App\Enums\OrderStatus::CONFIRMED => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                        \App\Enums\OrderStatus::CANCELLED => 'bg-rose-50 text-rose-800 border-rose-200',
                        default => 'bg-amber-50 text-amber-800 border-amber-200',
                    };

                    $paymentBadge = match($order->payment_status) {
                        \App\Enums\PaymentStatus::PAID => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        \App\Enums\PaymentStatus::PARTIALLY_REFUNDED => 'bg-purple-50 text-purple-800 border-purple-200',
                        \App\Enums\PaymentStatus::REFUNDED => 'bg-slate-100 text-slate-800 border-slate-300',
                        \App\Enums\PaymentStatus::FAILED, \App\Enums\PaymentStatus::CANCELLED => 'bg-rose-50 text-rose-800 border-rose-200',
                        default => 'bg-stone-100 text-stone-700 border-stone-200',
                    };
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border {{ $statusBadge }}">
                    {{ $order->status->value }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border {{ $paymentBadge }}">
                    Payment: {{ $order->payment_status->value }}
                </span>
            </div>
            <p class="text-xs text-stone-500">
                Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}
            </p>
        </div>

        <div class="flex flex-col sm:items-end gap-3">
            <div class="text-left sm:text-right">
                <span class="text-[10px] uppercase font-bold text-stone-400 block">Total Amount</span>
                <span class="text-2xl font-black text-emerald-950">₹{{ number_format((float) $order->grand_total, 2) }}</span>
            </div>
            <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                @if($order->payment_status === \App\Enums\PaymentStatus::PENDING && $order->status === \App\Enums\OrderStatus::PENDING)
                    <a href="{{ route('checkout.payment', $order->order_number) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Pay Now Online
                    </a>
                @endif
                @if(app(\App\Services\Invoice\InvoiceService::class)->canGenerateInvoice($order))
                    <a href="{{ route('account.orders.invoice', $order->order_number) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download Tax Invoice (PDF)
                    </a>
                @endif
                @if(!empty($canReturn) && $canReturn)
                    <a href="{{ route('account.orders.return', $order->order_number) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Request Return / Refund
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Shipment & Tracking Details (Displayed when shipment exists) --}}
    @php
        $latestShipment = $order->shipments->sortByDesc('id')->first();
    @endphp

    @if($latestShipment)
        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-emerald-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between gap-4 flex-wrap pb-3 border-b border-stone-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <h2 class="text-base sm:text-lg font-bold text-stone-900">Shipment & Delivery Details</h2>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                    {{ $latestShipment->shipping_status->value }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-stone-400 font-medium block">Courier / Carrier</span>
                    <span class="font-bold text-stone-900 text-sm">{{ $latestShipment->carrier ?: 'Standard Nursery Delivery' }}</span>
                </div>
                <div>
                    <span class="text-stone-400 font-medium block">Tracking / AWB Number</span>
                    <span class="font-mono font-bold text-stone-900 text-sm">{{ $latestShipment->tracking_number ?: 'Assigned on Dispatch' }}</span>
                </div>
                <div>
                    <span class="text-stone-400 font-medium block">Dispatched Date</span>
                    <span class="font-mono text-stone-700 text-xs">{{ $latestShipment->shipped_at ? $latestShipment->shipped_at->format('M d, Y h:i A') : '—' }}</span>
                </div>
                <div>
                    <span class="text-stone-400 font-medium block">
                        {{ $latestShipment->delivered_at ? 'Delivered Date' : 'Estimated Delivery' }}
                    </span>
                    <span class="font-mono font-bold {{ $latestShipment->delivered_at ? 'text-emerald-700' : 'text-stone-900' }} text-xs">
                        @if($latestShipment->delivered_at)
                            {{ $latestShipment->delivered_at->format('M d, Y h:i A') }}
                        @elseif($latestShipment->estimated_delivery_at)
                            {{ $latestShipment->estimated_delivery_at->format('M d, Y') }}
                        @else
                            In Transit
                        @endif
                    </span>
                </div>
            </div>

            @if(!empty($latestShipment->items_snapshot))
                <div class="pt-3 border-t border-stone-100 space-y-2">
                    <span class="text-xs font-bold text-stone-700 block uppercase tracking-wider">Items Included in Shipment</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($latestShipment->items_snapshot as $shippedItem)
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-stone-50 border border-stone-100 text-xs">
                                <div class="min-w-0 pr-2">
                                    <span class="font-bold text-stone-800 block truncate">{{ $shippedItem['product_name'] ?? 'Botanical Item' }}</span>
                                    @if(!empty($shippedItem['variant_name']))
                                        <span class="text-stone-500 text-[11px] block">{{ $shippedItem['variant_name'] }}</span>
                                    @endif
                                </div>
                                <span class="font-bold text-emerald-800 shrink-0 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                                    Qty: {{ $shippedItem['quantity'] ?? 1 }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($latestShipment->tracking_url)
                <div class="pt-2 flex items-center justify-between flex-wrap gap-3">
                    <p class="text-xs text-stone-500">Live courier tracking is available on the courier partner's portal.</p>
                    <a href="{{ $latestShipment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 shadow-sm transition-colors">
                        <span>Track Package Online</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Returns & Refunds Details (Displayed when returns or refunds exist) --}}
    @if($order->returns->isNotEmpty() || $order->refunds->isNotEmpty())
        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-amber-200/80 shadow-sm space-y-6">
            <div class="flex items-center justify-between gap-4 flex-wrap pb-3 border-b border-stone-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h2 class="text-base sm:text-lg font-bold text-stone-900">Returns &amp; Refunds</h2>
                </div>
            </div>

            @if($order->returns->isNotEmpty())
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500">Return Requests</h3>
                    <div class="space-y-3">
                        @foreach($order->returns as $ret)
                            @php
                                $retBadge = match($ret->status) {
                                    \App\Enums\ReturnStatus::REQUESTED => 'bg-amber-50 text-amber-800 border-amber-200',
                                    \App\Enums\ReturnStatus::APPROVED => 'bg-blue-50 text-blue-800 border-blue-200',
                                    \App\Enums\ReturnStatus::REJECTED => 'bg-rose-50 text-rose-800 border-rose-200',
                                    \App\Enums\ReturnStatus::COMPLETED => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    default => 'bg-stone-100 text-stone-700 border-stone-200',
                                };
                            @endphp
                            <div class="p-4 rounded-2xl bg-stone-50/80 border border-stone-200/70 text-xs space-y-2">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div>
                                        <span class="font-bold text-stone-900 text-sm block">{{ $ret->orderItem?->product_name ?? 'Botanical Item' }}</span>
                                        <span class="text-stone-500 text-[11px] block">{{ $ret->orderItem?->variant_name }} &bull; SKU: {{ $ret->orderItem?->sku }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase border {{ $retBadge }}">
                                            {{ $ret->status->value }}
                                        </span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-stone-200/50 text-[11px] text-stone-600">
                                    <div>
                                        <span class="text-stone-400 block">Quantity Returned:</span>
                                        <span class="font-bold text-stone-900">{{ $ret->quantity }} unit(s)</span>
                                    </div>
                                    <div>
                                        <span class="text-stone-400 block">Estimated Refund:</span>
                                        <span class="font-bold text-emerald-800">₹{{ number_format((float) $ret->refund_amount, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-stone-400 block">Requested On:</span>
                                        <span class="font-mono text-stone-800">{{ $ret->created_at->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="pt-1 text-[11px]">
                                    <span class="text-stone-400">Customer Reason:</span>
                                    <span class="text-stone-700 font-medium italic">"{{ $ret->reason }}"</span>
                                </div>
                                @if($ret->admin_notes)
                                    <div class="pt-1 text-[11px] bg-white p-2.5 rounded-xl border border-stone-200 text-stone-700">
                                        <span class="font-bold text-stone-900 block">Nursery Note:</span>
                                        <span>{{ $ret->admin_notes }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($order->refunds->isNotEmpty())
                <div class="pt-3 border-t border-stone-100 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500">Processed Refund History</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-stone-700">
                            <thead class="border-b border-stone-200 text-[10px] uppercase font-bold text-stone-400 bg-stone-50">
                                <tr>
                                    <th class="py-2 px-3">Refund ID</th>
                                    <th class="py-2 px-3">Amount</th>
                                    <th class="py-2 px-3">Status</th>
                                    <th class="py-2 px-3">Gateway</th>
                                    <th class="py-2 px-3">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach($order->refunds as $ref)
                                    <tr>
                                        <td class="py-2.5 px-3 font-mono font-bold text-stone-900">{{ $ref->gateway_refund_id ?: ('#' . $ref->id) }}</td>
                                        <td class="py-2.5 px-3 font-bold text-emerald-800">₹{{ number_format((float) $ref->amount, 2) }}</td>
                                        <td class="py-2.5 px-3">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $ref->status === \App\Enums\RefundStatus::PROCESSED ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }}">
                                                {{ $ref->status->value }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 font-mono text-[11px]">{{ ucfirst($ref->gateway) }}</td>
                                        <td class="py-2.5 px-3 font-mono text-[11px] text-stone-500">{{ $ref->created_at->format('M d, Y h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

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
