@extends('layouts.storefront')

@section('seo')
    <title>Secure Checkout | Sugandha Farms and Nursery</title>
    <meta name="description" content="Complete your nursery plant order securely with Sugandha Farms and Nursery.">
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-stone-500 mb-6 sm:mb-8">
        <a href="{{ route('home') }}" class="hover:text-emerald-800 transition-colors">Home</a>
        <span>/</span>
        <a href="{{ route('cart.index') }}" class="hover:text-emerald-800 transition-colors">Cart</a>
        <span>/</span>
        <span class="font-semibold text-stone-900">Checkout</span>
    </nav>

    {{-- Error Banner --}}
    @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <div class="font-bold mb-1 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 ml-7">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" x-data="{ billingSame: true }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            {{-- Left Column: Checkout Steps (Cols 1-7) --}}
            <div class="lg:col-span-7 space-y-8">
                {{-- Step 1: Delivery Address --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-stone-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-emerald-800 text-white font-bold text-sm flex items-center justify-center">1</span>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-stone-900">Select Delivery Address</h2>
                                <p class="text-xs text-stone-500">Choose the destination for your plants</p>
                            </div>
                        </div>
                        <a href="{{ route('account.addresses.index') }}"
                           class="text-xs font-semibold text-emerald-800 hover:text-emerald-950 flex items-center gap-1">
                            <span>+ Manage / Add Address</span>
                        </a>
                    </div>

                    {{-- Delhi NCR Delivery Policy Notice --}}
                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 space-y-2 text-xs">
                        <div class="flex items-center gap-2 font-bold text-emerald-900">
                            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Delivery Highlights</span>
                        </div>
                        <ul class="text-emerald-950 font-medium space-y-1 ml-6 list-disc">
                            <li>Delivery available only within Delhi NCR.</li>
                            <li>Delivery is expected within 3 days.</li>
                            <li>Orders ABOVE ₹1,000 qualify for FREE delivery.</li>
                        </ul>
                    </div>

                    @if($addresses->isEmpty())
                        <div class="p-6 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-center space-y-3">
                            <svg class="w-8 h-8 text-amber-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-sm font-semibold text-amber-900">No saved addresses found</p>
                            <p class="text-xs text-amber-700 max-w-sm mx-auto">Please add a delivery address to your address book before placing your order.</p>
                            <a href="{{ route('account.addresses.index') }}"
                               class="inline-block py-2.5 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-semibold shadow-sm transition-colors">
                                Add Address in Address Book
                            </a>
                        </div>
                    @else
                        @php
                            $hasDelhiNcrAddress = $addresses->contains(fn ($a) => $a->isDelhiNcr());
                        @endphp

                        @if(! $hasDelhiNcrAddress)
                            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 text-xs sm:text-sm space-y-2">
                                <div class="flex items-center gap-2 font-bold text-amber-950">
                                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>No Eligible Delhi NCR Address Found</span>
                                </div>
                                <p class="text-xs text-amber-800">
                                    All your saved addresses are located outside Delhi NCR. We currently deliver exclusively within Delhi NCR. Please add a Delhi NCR address to place this order.
                                </p>
                                <div>
                                    <a href="{{ route('account.addresses.index') }}"
                                       class="inline-flex items-center px-3.5 py-2 rounded-xl bg-amber-800 hover:bg-amber-900 text-white font-bold text-xs shadow-sm transition-colors">
                                        + Add Delhi NCR Address
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            @foreach($addresses as $addr)
                                @php
                                    $isSelected = (int) old('shipping_address_id', $defaultAddress?->id ?? $addresses->first()->id) === $addr->id;
                                @endphp
                                <label class="relative flex items-start gap-4 p-4 sm:p-5 rounded-2xl border cursor-pointer transition-all {{ $isSelected ? 'border-emerald-600 bg-emerald-50/30 ring-2 ring-emerald-600/10' : 'border-stone-200 hover:border-stone-300 bg-white' }}">
                                    <input type="radio"
                                           name="shipping_address_id"
                                           value="{{ $addr->id }}"
                                           {{ $isSelected ? 'checked' : '' }}
                                           class="mt-1 text-emerald-800 focus:ring-emerald-800 h-4 w-4 border-stone-300">
                                    <div class="flex-grow min-w-0 space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-stone-900 text-sm sm:text-base">{{ $addr->recipient_name }}</span>
                                            <span class="text-xs font-mono text-stone-500">({{ $addr->phone }})</span>
                                            @if($addr->is_default)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">Default</span>
                                            @endif
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-stone-100 text-stone-600">{{ ucfirst($addr->address_type?->value ?? 'home') }}</span>
                                            @if($addr->isDelhiNcr())
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">Delhi NCR</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800" title="Delivery available only within Delhi NCR">Outside Delhi NCR</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-stone-600 leading-relaxed">
                                            {{ $addr->address_line_1 }}@if($addr->address_line_2), {{ $addr->address_line_2 }}@endif,
                                            {{ $addr->city }}, {{ $addr->state }} - <span class="font-mono">{{ $addr->postal_code }}</span>
                                        </p>
                                        @if(!$addr->isDelhiNcr())
                                            <p class="text-[11px] text-amber-700 font-semibold mt-1">This address is outside Delhi NCR and cannot be used for delivery.</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Step 2: Billing Address Preference --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 border-b border-stone-100 pb-4">
                        <span class="w-8 h-8 rounded-full bg-emerald-800 text-white font-bold text-sm flex items-center justify-center">2</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-stone-900">Billing Address</h2>
                            <p class="text-xs text-stone-500">Tax invoice and billing details</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox"
                               name="billing_same_as_shipping"
                               value="1"
                               x-model="billingSame"
                               class="rounded border-stone-300 text-emerald-800 focus:ring-emerald-800 h-4 w-4">
                        <span class="text-sm font-medium text-stone-800">Billing address is same as delivery address</span>
                    </label>

                    <div x-show="!billingSame" x-cloak class="pt-4 border-t border-stone-100 space-y-3">
                        <p class="text-xs font-semibold text-stone-700">Choose separate billing address:</p>
                        @foreach($addresses as $addr)
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-stone-200 text-xs cursor-pointer hover:bg-stone-50">
                                <input type="radio" name="billing_address_id" value="{{ $addr->id }}" class="mt-0.5 text-emerald-800 focus:ring-emerald-800 h-3.5 w-3.5">
                                <div>
                                    <span class="font-bold text-stone-900">{{ $addr->recipient_name }}</span> &bull; {{ $addr->city }}, {{ $addr->state }} ({{ $addr->postal_code }})
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Step 3: Delivery Instructions / Notes --}}
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 border-b border-stone-100 pb-4">
                        <span class="w-8 h-8 rounded-full bg-emerald-800 text-white font-bold text-sm flex items-center justify-center">3</span>
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-stone-900">Order Notes (Optional)</h2>
                            <p class="text-xs text-stone-500">Special nursery handling or delivery instructions</p>
                        </div>
                    </div>

                    <textarea name="notes"
                              rows="2"
                              maxlength="1000"
                              placeholder="e.g. Please leave with security guard, or delivery preferred during morning hours..."
                              class="w-full px-4 py-3 rounded-2xl border border-stone-300 text-xs sm:text-sm text-stone-900 focus:ring-2 focus:ring-emerald-800 focus:border-emerald-800 transition-all">{{ old('notes') }}</textarea>
                </div>

                {{-- Step 4: Payment Method Notice --}}
                @if(config('payment.default') === 'razorpay')
                    <div class="bg-emerald-50/50 rounded-3xl p-6 border border-emerald-200/80 space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="p-2 rounded-xl bg-emerald-100 text-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-stone-900">Secure Online Payment (Razorpay)</h3>
                                <p class="text-xs text-stone-600">UPI, Cards, NetBanking, and Wallets</p>
                            </div>
                        </div>
                        <p class="text-xs text-stone-500 leading-relaxed">
                            Complete your payment securely via Razorpay Standard Checkout upon clicking Place Order. All transactions are encrypted and authentic.
                        </p>
                    </div>
                @else
                    <div class="bg-emerald-50/50 rounded-3xl p-6 border border-emerald-200/80 space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="p-2 rounded-xl bg-emerald-100 text-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-stone-900">Order Verification & Dispatch</h3>
                                <p class="text-xs text-stone-600">Verified Greenhouse Reservation</p>
                            </div>
                        </div>
                        <p class="text-xs text-stone-500 leading-relaxed">
                            Placing your order securely reserves inventory from our nursery greenhouse and schedules root-hydrated dispatch to your Delhi NCR destination.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Right Column: Order Summary (Cols 8-12) --}}
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-6 sticky top-24">
                    <div class="border-b border-stone-100 pb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-stone-900">Order Summary</h2>
                        <span class="text-xs font-semibold text-stone-500 font-mono">{{ count($pricing['lines']) }} {{ Str::plural('Item', count($pricing['lines'])) }}</span>
                    </div>

                    {{-- Items List --}}
                    <div class="divide-y divide-stone-100 max-h-72 overflow-y-auto pr-1 space-y-3">
                        @foreach($pricing['lines'] as $line)
                            @php
                                $variant = $line['variant'];
                                $product = $variant->product;
                                $primaryImage = $product?->primaryImage ?? $product?->images?->first();
                            @endphp
                            <div class="pt-3 first:pt-0 flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-stone-100 border border-stone-200 overflow-hidden shrink-0">
                                    @if($primaryImage?->url)
                                        <img src="{{ $primaryImage->url }}" alt="{{ $product?->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-stone-300 text-xs font-bold">N/A</div>
                                    @endif
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-stone-900 truncate">{{ $product?->name }}</h4>
                                    <div class="text-[11px] text-stone-500 font-mono">
                                        Qty: {{ $line['quantity'] }} &times; ₹{{ number_format((float) $line['unit_price'], 2) }}
                                    </div>
                                </div>
                                <div class="text-xs sm:text-sm font-extrabold text-stone-900 shrink-0">
                                    ₹{{ number_format((float) $line['subtotal'], 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Financial Breakdown --}}
                    <div class="pt-4 border-t border-stone-200 space-y-3 text-xs sm:text-sm">
                        <div class="flex items-center justify-between text-stone-600">
                            <span>Subtotal</span>
                            <span class="font-semibold text-stone-900">₹{{ number_format((float) $pricing['subtotal'], 2) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-stone-600">
                            <span>Estimated Tax</span>
                            <span class="font-semibold text-stone-900">₹{{ number_format((float) $pricing['tax_amount'], 2) }}</span>
                        </div>

                        <div class="flex items-start justify-between text-stone-600">
                            <div>
                                <span>Delivery / Shipping</span>
                                <span class="block text-[11px] text-stone-500">Delhi NCR only &bull; within 3 days</span>
                            </div>
                            <div class="text-right">
                                @if(bccomp((string) $pricing['subtotal'], '1000.00', 2) > 0 && bccomp((string) $pricing['shipping_amount'], '0.00', 2) === 0)
                                    <span class="font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded text-xs">FREE</span>
                                @elseif(bccomp((string) $pricing['shipping_amount'], '0.00', 2) === 0)
                                    <span class="font-semibold text-stone-900">₹0.00</span>
                                @else
                                    <span class="font-semibold text-stone-900">₹{{ number_format((float) $pricing['shipping_amount'], 2) }}</span>
                                @endif
                            </div>
                        </div>

                        @if(bccomp((string) $pricing['discount_amount'], '0.00', 2) > 0)
                            <div class="flex items-center justify-between text-emerald-700">
                                <span>Discount</span>
                                <span class="font-semibold">-₹{{ number_format((float) $pricing['discount_amount'], 2) }}</span>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-stone-200 flex items-baseline justify-between">
                            <span class="font-bold text-stone-900 text-base">Grand Total</span>
                            <span class="text-2xl font-black text-emerald-950 tracking-tight">₹{{ number_format((float) $pricing['grand_total'], 2) }}</span>
                        </div>
                    </div>

                    {{-- Delivery Transparency Highlight --}}
                    @if(bccomp((string) $pricing['subtotal'], '1000.00', 2) > 0)
                        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-start gap-2.5">
                            <span class="text-base shrink-0 mt-0.5" aria-hidden="true">🎉</span>
                            <div>
                                <span class="font-bold text-emerald-950">You qualify for FREE delivery</span>
                                <p class="text-[11px] text-emerald-800 font-medium mt-0.5">
                                    Orders ABOVE ₹1,000 qualify for free delivery &bull; Delhi NCR only &bull; Delivery within 3 days
                                </p>
                            </div>
                        </div>
                    @elseif(bccomp((string) $pricing['subtotal'], '1000.00', 2) === 0)
                        <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <span class="font-bold text-amber-950">Add a little more to qualify for FREE delivery</span>
                                <p class="text-[11px] text-amber-800 font-medium mt-0.5">
                                    Orders ABOVE ₹1,000 qualify for free delivery &bull; Delhi NCR only &bull; Delivery within 3 days
                                </p>
                            </div>
                        </div>
                    @else
                        @php
                            $remainingForFree = bcsub('1000.00', (string) $pricing['subtotal'], 2);
                        @endphp
                        <div class="p-3.5 rounded-2xl bg-stone-50 border border-stone-200 text-xs text-stone-700 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <span class="font-bold text-stone-900">Add ₹{{ number_format((float) $remainingForFree, 2) }} more for FREE delivery</span>
                                <p class="text-[11px] text-stone-600 font-medium mt-0.5">
                                    Orders ABOVE ₹1,000 qualify for free delivery &bull; Delhi NCR only &bull; Delivery within 3 days
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Submit CTA --}}
                    @if($addresses->isEmpty())
                        <button type="button" disabled
                                class="w-full py-4 px-6 rounded-2xl bg-stone-200 text-stone-400 font-bold text-sm cursor-not-allowed text-center">
                            Add Address to Place Order
                        </button>
                    @else
                        <button type="submit"
                                id="place-order-btn"
                                class="w-full py-4 px-6 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20 transition-all hover:scale-[1.01] active:scale-[0.99]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Place Order Now</span>
                        </button>
                    @endif

                    <div class="text-center">
                        <a href="{{ route('cart.index') }}" class="text-xs text-stone-500 hover:text-stone-800 transition-colors">
                            &larr; Return to Shopping Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@if(config('payment.default') === 'razorpay')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('checkout-form');
    const submitBtn = document.getElementById('place-order-btn');
    if (!form || !submitBtn) return;

    form.addEventListener('submit', function (e) {
        if (typeof Razorpay === 'undefined') {
            return;
        }

        e.preventDefault();
        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span>Processing Order...</span>';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData.get('_token')
            },
            body: formData
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
                if (data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    alert(data.errors[firstKey][0]);
                } else {
                    alert(data.message || 'An error occurred during checkout.');
                }
                return;
            }

            if (data.gateway === 'razorpay' && data.razorpay_order_id) {
                const options = {
                    key: data.key_id,
                    amount: data.amount,
                    currency: data.currency || 'INR',
                    name: 'Sugandha Farms and Nursery',
                    description: 'Order #' + data.order_number,
                    order_id: data.razorpay_order_id,
                    prefill: {
                        name: data.customer_name,
                        email: data.customer_email,
                        contact: data.customer_phone
                    },
                    theme: { color: '#065f46' },
                    handler: function (response) {
                        submitBtn.innerHTML = '<span>Verifying payment with bank...</span>';
                        fetch(@json(route('checkout.payment.verify')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': formData.get('_token')
                            },
                            body: JSON.stringify({
                                transaction_number: data.transaction_number,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature
                            })
                        })
                        .then(r => r.json())
                        .then(verifyData => {
                            if (verifyData.success && verifyData.redirect_url) {
                                window.location.href = verifyData.redirect_url;
                            } else {
                                window.location.href = '/checkout/payment/' + data.order_number;
                            }
                        })
                        .catch(() => {
                            window.location.href = '/checkout/payment/' + data.order_number;
                        });
                    },
                    modal: {
                        ondismiss: function () {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                            window.location.href = '/checkout/payment/' + data.order_number;
                        }
                    }
                };

                const rzp = new Razorpay(options);
                rzp.open();
            } else if (data.redirect_url) {
                window.location.href = data.redirect_url;
            }
        })
        .catch(() => {
            form.submit();
        });
    });
});
</script>
@endif
@endsection
