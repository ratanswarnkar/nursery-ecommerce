@extends('layouts.storefront')

@section('title', 'Terms & Conditions | Sugandha Farms and Nursery')
@section('meta_description', 'Terms and Conditions governing orders, delivery, and services provided by Sugandha Farms and Nursery in Delhi NCR.')

@section('content')
<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-xs text-slate-500 mb-6 gap-2">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Terms & Conditions</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-12">
            <div class="border-b border-slate-100 pb-6 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Legal Agreement
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Terms & Conditions</h1>
                <p class="text-sm text-slate-500 mt-2">Last updated: {{ date('F Y') }} &bull; Sugandha Farms and Nursery</p>
            </div>

            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-6">
                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">1. Acceptance of Terms</h2>
                    <p>
                        By browsing our website, registering an account, or placing an order with <strong>Sugandha Farms and Nursery</strong>, you agree to be bound by these Terms and Conditions and our associated operational policies.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">2. Service Geography & Deliveries</h2>
                    <p>
                        Deliveries are exclusively restricted to serviceable locations within <strong>Delhi NCR</strong>. Orders placed for addresses outside this territorial boundary cannot be accepted. Orders above ₹1,000 qualify for free delivery, and our standard fulfilment timeline is within 3 days from order confirmation.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">3. Botanical Variations & Care</h2>
                    <p>
                        Plants and nursery saplings are living organic organisms. Minor natural variations in height, foliage density, coloration, and leaf count are expected characteristics of living plants and do not constitute defects. We provide basic plant care guides to support plant health upon arrival.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">4. Pricing & Payments</h2>
                    <p>
                        All prices listed on the storefront are denominated in Indian Rupees (INR). Orders must be paid in full using available payment channels (including online payment through Razorpay). Orders remain in a pending state until payment verification is successfully completed.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">5. Returns, Cancellations & Refunds</h2>
                    <p>
                        Returns, cancellations, and refunds are governed strictly by our <a href="{{ route('policy.refund') }}" class="text-emerald-700 underline font-medium">Cancellation & Refund Policy</a>. Customers may request returns for delivered items through their online account portal. All returns and refunds are subject to administrative review and system-calculated balance validation.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">6. Governing Law</h2>
                    <p>
                        These terms and any contracts formed with Sugandha Farms and Nursery shall be governed by and construed in accordance with the laws of India, under the jurisdiction of the courts of Delhi.
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
