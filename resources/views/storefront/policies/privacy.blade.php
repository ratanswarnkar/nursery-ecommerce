@extends('layouts.storefront')

@section('title', 'Privacy Policy | Sugandha Farms and Nursery')
@section('meta_description', 'Privacy Policy for Sugandha Farms and Nursery detailing our data collection, customer security, and order privacy practices.')

@section('content')
<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-xs text-slate-500 mb-6 gap-2">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Privacy Policy</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-12">
            <div class="border-b border-slate-100 pb-6 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Data Privacy & Security
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Privacy Policy</h1>
                <p class="text-sm text-slate-500 mt-2">Last updated: {{ date('F Y') }} &bull; Sugandha Farms and Nursery</p>
            </div>

            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-6">
                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">1. Information We Collect</h2>
                    <p>
                        Sugandha Farms and Nursery collects essential customer information required to process and deliver your orders accurately. This includes:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-700">
                        <li>Customer contact details: Mobile phone number (used for passwordless OTP authentication and delivery SMS).</li>
                        <li>Recipient information: Full name, delivery address within Delhi NCR, PIN code, and landmark instructions.</li>
                        <li>Transactional information: Order numbers, items purchased, totals, and payment status.</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">2. How We Protect Payment Information</h2>
                    <p>
                        We do not collect, store, or process raw credit/debit card numbers, CVVs, or net banking credentials on our servers. All digital payments are processed through PCI-DSS compliant payment gateways (such as Razorpay). Gateway secrets and sensitive tokens are encrypted server-side and never exposed to the client browser.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">3. Purpose of Data Processing</h2>
                    <p>
                        Customer data is utilized strictly for operational purposes:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-700">
                        <li>Authenticating customer sessions via secure time-limited OTP codes.</li>
                        <li>Coordinating botanical logistics, dispatch, and physical doorstep delivery within Delhi NCR.</li>
                        <li>Providing electronic tax invoices and managing order lifecycle status updates.</li>
                        <li>Facilitating customer return inquiries and authorized refunds.</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">4. Third-Party Sharing</h2>
                    <p>
                        We do not sell, rent, or trade your personal data with third-party advertisers. Information is shared only with verified service providers necessary for order fulfillment (authorized delivery drivers and payment processing gateways).
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">5. Contact Information</h2>
                    <p>
                        For any questions or concerns regarding your personal data, please reach out to us at:
                    </p>
                    <div class="text-xs text-slate-600 bg-slate-100/80 p-4 rounded-lg space-y-1">
                        <p><strong>Sugandha Farms and Nursery</strong></p>
                        <p>Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082</p>
                        <p>Phone: 098111 14365</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
