@extends('layouts.storefront')

@section('seo')
    <title>Complete Payment #{{ $order->order_number }} | Sugandha Farms and Nursery</title>
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-stone-500 mb-6 sm:mb-8">
        <a href="{{ route('home') }}" class="hover:text-emerald-800 transition-colors">Home</a>
        <span>/</span>
        <a href="{{ route('account.orders.index') }}" class="hover:text-emerald-800 transition-colors">Orders</a>
        <span>/</span>
        <span class="font-semibold text-stone-900">Payment</span>
    </nav>

    {{-- Error Banner --}}
    <div id="payment-error-banner" class="hidden mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h4 class="font-bold text-sm">Payment Notice</h4>
                <p id="payment-error-text" class="text-xs text-rose-700 mt-0.5"></p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Main Payment Card --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-stone-100 pb-4">
            <div>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-900 mb-1">
                    Awaiting Payment
                </span>
                <h1 class="text-xl sm:text-2xl font-bold text-stone-900">Complete Your Order Payment</h1>
                <p class="text-xs text-stone-500">Order Reference: <span class="font-mono font-bold text-stone-800">#{{ $order->order_number }}</span></p>
            </div>
            <div class="text-right">
                <span class="text-xs text-stone-500">Grand Total</span>
                <div class="text-2xl sm:text-3xl font-black text-emerald-950">
                    ₹{{ number_format((float) $order->grand_total, 2) }}
                </div>
            </div>
        </div>

        {{-- Order Items Preview --}}
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500">Order Summary ({{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }})</h3>
            <div class="divide-y divide-stone-100 max-h-48 overflow-y-auto">
                @foreach($order->items as $item)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div class="truncate pr-4">
                            <span class="font-semibold text-stone-800">{{ $item->product_name }}</span>
                            <span class="text-stone-400 font-mono"> &times; {{ $item->quantity }}</span>
                        </div>
                        <span class="font-bold text-stone-900 shrink-0">₹{{ number_format((float) $item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Delivery Destination --}}
        @php $shipAddr = $order->shipping_address_json; @endphp
        <div class="p-4 rounded-2xl bg-stone-50 border border-stone-100 text-xs space-y-1">
            <div class="font-bold text-stone-700">Delivery Address:</div>
            <p class="text-stone-600">
                {{ $shipAddr['recipient_name'] ?? $customer->name }}, {{ $shipAddr['address_line_1'] ?? '' }}, {{ $shipAddr['city'] ?? '' }} - {{ $shipAddr['postal_code'] ?? '' }}
            </p>
        </div>

        {{-- Gateway Information Badge --}}
        <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-100 flex items-center gap-3">
            <div class="p-2 rounded-xl bg-emerald-100 text-emerald-800 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="text-xs">
                <p class="font-bold text-stone-900">Secure Payment via Razorpay</p>
                <p class="text-stone-500">Supports UPI (GPay, PhonePe, Paytm), Credit/Debit Cards, NetBanking, and Wallets.</p>
            </div>
        </div>

        {{-- Pay Button CTA --}}
        <div class="pt-2 space-y-3">
            <button type="button"
                    id="rzp-pay-button"
                    class="w-full py-4 px-6 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-base flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span id="rzp-button-text">Pay ₹{{ number_format((float) $order->grand_total, 2) }} with Razorpay</span>
            </button>

            <div class="text-center">
                <a href="{{ route('account.orders.show', $order->order_number) }}"
                   class="text-xs text-stone-500 hover:text-stone-800 transition-colors">
                    &larr; View order in account
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const payBtn = document.getElementById('rzp-pay-button');
    const payBtnText = document.getElementById('rzp-button-text');
    const errorBanner = document.getElementById('payment-error-banner');
    const errorText = document.getElementById('payment-error-text');

    function showError(message) {
        errorText.textContent = message;
        errorBanner.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideError() {
        errorBanner.classList.add('hidden');
    }

    const options = {
        key: @json($keyId),
        amount: @json($amountInPaise),
        currency: 'INR',
        name: 'Sugandha Farms and Nursery',
        description: 'Order #{{ $order->order_number }}',
        order_id: @json($razorpayOrderId),
        prefill: {
            name: @json($customer->name),
            email: @json($customer->email),
            contact: @json($customer->phone),
        },
        theme: {
            color: '#065f46'
        },
        handler: function (response) {
            hideError();
            payBtn.disabled = true;
            payBtnText.textContent = 'Verifying payment with bank...';

            fetch(@json(route('checkout.payment.verify')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify({
                    transaction_number: @json($transaction->transaction_number),
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    payBtn.disabled = false;
                    payBtnText.textContent = 'Retry Payment';
                    showError(data.message || 'Payment verification failed. Please contact support.');
                }
            })
            .catch(() => {
                payBtn.disabled = false;
                payBtnText.textContent = 'Retry Payment';
                showError('A network error occurred while verifying your payment. Please refresh.');
            });
        },
        modal: {
            ondismiss: function () {
                showError('Payment modal was closed. You can click the button below to resume payment anytime.');
                fetch(@json(route('checkout.payment.cancel')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token())
                    },
                    body: JSON.stringify({
                        transaction_number: @json($transaction->transaction_number),
                        reason: 'Modal dismissed by user'
                    })
                }).catch(() => {});
            }
        }
    };

    let rzp = null;
    try {
        rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            showError('Payment failed: ' + (response.error.description || 'Transaction declined by bank.'));
        });
    } catch (e) {
        console.error('Razorpay SDK error', e);
    }

    payBtn.addEventListener('click', function (e) {
        e.preventDefault();
        hideError();
        if (rzp) {
            rzp.open();
        } else {
            showError('Payment gateway could not be loaded. Please check your internet connection.');
        }
    });

    // Auto-open on initial load if order_id is present
    if (rzp && options.order_id) {
        setTimeout(function () {
            rzp.open();
        }, 500);
    }
});
</script>
@endsection
