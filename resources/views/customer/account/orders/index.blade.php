@extends('layouts.storefront')

@section('seo')
    <title>My Orders | Sugandha Farms and Nursery</title>
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="space-y-8">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Account', 'url' => route('account.dashboard')],
        ['name' => 'Orders', 'url' => '']
    ]" />

    {{-- Orders Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">Order History</h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">Review past nursery purchases, receipts, and order statuses.</p>
        </div>
        <a href="{{ route('shop.index') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-semibold shadow-sm transition-colors self-start sm:self-auto">
            Browse Plants &rarr;
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="p-16 text-center rounded-3xl bg-white border border-stone-200/80 shadow-sm space-y-4">
            <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-800 mx-auto flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h2 class="text-base sm:text-lg font-bold text-stone-900">No orders placed yet</h2>
            <p class="text-xs text-stone-500 max-w-sm mx-auto">
                Explore our lush collection of indoor plants, fruit saplings, and organic potting soils.
            </p>
            <div>
                <a href="{{ route('shop.index') }}" class="inline-flex items-center px-5 py-3 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-bold transition-all shadow-md">
                    Start Shopping Catalog
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-stone-200/80 shadow-sm overflow-hidden divide-y divide-stone-100">
            @foreach($orders as $order)
                <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6 hover:bg-stone-50/50 transition-colors">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="font-mono font-extrabold text-base text-emerald-950">#{{ $order->order_number }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200/60">
                                {{ $order->status->value }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-stone-100 text-stone-700">
                                Payment: {{ $order->payment_status->value }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-500">
                            Ordered on {{ $order->created_at->format('M d, Y') }} &bull; {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-6 pt-4 sm:pt-0 border-t sm:border-t-0 border-stone-100">
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-stone-400 block">Total Amount</span>
                            <span class="text-lg font-black text-stone-900">₹{{ number_format((float) $order->grand_total, 2) }}</span>
                        </div>
                        <a href="{{ route('account.orders.show', $order->order_number) }}"
                           class="py-2.5 px-4 rounded-xl bg-stone-100 hover:bg-emerald-800 hover:text-white text-xs font-semibold text-stone-800 transition-all shadow-sm">
                            View Order &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
