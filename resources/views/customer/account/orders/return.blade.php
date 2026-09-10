@extends('layouts.storefront')

@section('title', 'Request Return — Order #' . $order->order_number . ' | Sugandha Farms and Nursery')

@section('content')
<div class="bg-slate-50 min-h-screen py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-xs text-slate-500 mb-6 gap-2">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <a href="{{ route('account.orders.index') }}" class="hover:text-emerald-700 transition">My Orders</a>
            <span>/</span>
            <a href="{{ route('account.orders.show', $order->order_number) }}" class="hover:text-emerald-700 transition">#{{ $order->order_number }}</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Request Return</span>
        </nav>

        <!-- Header -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200/80 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-6 mb-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-2">
                        Delivered Order
                    </span>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Request Return / Refund</h1>
                    <p class="text-xs text-slate-500 mt-1">Order #{{ $order->order_number }} &bull; Placed on {{ $order->created_at->format('M d, Y') }}</p>
                </div>
                <a href="{{ route('account.orders.show', $order->order_number) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    &larr; Back to Order Details
                </a>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                    <p class="font-bold">Please correct the following errors:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('account.orders.return.store', $order->order_number) }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 mb-1">Select Items to Return</h2>
                        <p class="text-xs text-slate-500 mb-4">Check the items you wish to return and enter the quantity.</p>

                        <div class="space-y-3">
                            @foreach ($items as $item)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white transition gap-4">
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            name="selected_items[]"
                                            id="item_{{ $item->id }}"
                                            value="{{ $item->id }}"
                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            {{ in_array($item->id, old('selected_items', [])) ? 'checked' : '' }}
                                        >
                                        <label for="item_{{ $item->id }}" class="cursor-pointer">
                                            <span class="block text-sm font-bold text-slate-900">{{ $item->product_name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $item->variant_name }} &bull; SKU: {{ $item->sku }}</span>
                                            <span class="block text-xs text-slate-600 mt-1 font-medium">
                                                Purchased: {{ $item->quantity }} &bull; Returnable: <strong class="text-emerald-700">{{ $item->returnable_quantity }}</strong> &bull; Unit Price: ₹{{ number_format($item->price, 2) }}
                                            </span>
                                        </label>
                                    </div>

                                    <div class="flex items-center gap-2 pl-7 sm:pl-0">
                                        <label for="qty_{{ $item->id }}" class="text-xs font-semibold text-slate-700">Return Qty:</label>
                                        <input
                                            type="number"
                                            name="quantities[{{ $item->id }}]"
                                            id="qty_{{ $item->id }}"
                                            min="1"
                                            max="{{ $item->returnable_quantity }}"
                                            value="{{ old('quantities.' . $item->id, 1) }}"
                                            class="w-20 rounded-lg border-slate-300 text-xs text-center focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-bold text-slate-900 mb-1">
                            Reason for Return <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-500 mb-2">Please explain the condition or reason (e.g., foliage transit damage, incorrect sapling variant, root shock upon arrival).</p>
                        <textarea
                            name="reason"
                            id="reason"
                            rows="4"
                            required
                            class="w-full rounded-xl border-slate-300 text-xs shadow-sm focus:border-emerald-500 focus:ring-emerald-500 placeholder-slate-400 p-3"
                            placeholder="Describe in detail why you are returning these botanical items..."
                        >{{ old('reason') }}</textarea>
                    </div>

                    <div class="p-4 bg-emerald-50 border border-emerald-200/80 rounded-xl text-xs text-emerald-950 space-y-1">
                        <p class="font-bold">Fair Returns & Refund Notice:</p>
                        <p>
                            Upon submission, our nursery administrators will review your request. If approved, pickup instructions will be provided and refunds are calculated server-side based on the verified item pricing and paid balance.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('account.orders.show', $order->order_number) }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                            Cancel
                        </a>
                        <button
                            type="submit"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition"
                        >
                            Submit Return Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
