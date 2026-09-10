@extends('layouts.storefront')

@section('title', 'Cancellation, Return & Refund Policy | Sugandha Farms and Nursery')
@section('meta_description', 'Customer return, cancellation, and refund guidelines for orders at Sugandha Farms and Nursery.')

@section('content')
<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-xs text-slate-500 mb-6 gap-2">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Cancellation & Refund Policy</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-12">
            <div class="border-b border-slate-100 pb-6 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Customer Protection & Fair Returns
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Cancellation, Return & Refund Policy</h1>
                <p class="text-sm text-slate-500 mt-2">Last updated: {{ date('F Y') }} &bull; Sugandha Farms and Nursery</p>
            </div>

            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-6">
                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">1. Order Cancellation (Before Dispatch)</h2>
                    <p>
                        Customers may request order cancellation prior to dispatch. When an order is cancelled prior to shipment, any reserved botanical inventory is immediately released back to our live nursery catalog, and refund processing is initiated in accordance with our administrative verification protocol.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">2. Return Eligibility for Delivered Items</h2>
                    <p>
                        To be eligible for a return or replacement request:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-700">
                        <li>The order must have reached the <strong>Delivered</strong> status.</li>
                        <li>The order payment must be confirmed and completed.</li>
                        <li>The requested items must have remaining returnable quantities that have not already been submitted in an active request.</li>
                    </ul>
                    <p>
                        Because living botanical specimens (potted plants, saplings, live foliage) are perishable natural goods, each return request is evaluated by our nursery horticultural specialists based on transit damage, physical condition upon receipt, or incorrect variant fulfillment.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">3. Return Request Submission Workflow</h2>
                    <p>
                        Customers can conveniently initiate a return request online directly from their account portal:
                    </p>
                    <ol class="list-decimal list-inside space-y-1.5 text-slate-700">
                        <li>Log in using your registered mobile number at the customer portal.</li>
                        <li>Navigate to <strong>My Orders</strong> and select the relevant delivered order.</li>
                        <li>Click <strong>Request Return / Refund</strong> on the order details page.</li>
                        <li>Select the specific item(s) and specify the quantity you wish to return.</li>
                        <li>Provide a detailed description of the reason for return (e.g. transit foliage damage, incorrect plant variant).</li>
                        <li>Submit the request for administrative review.</li>
                    </ol>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">4. Administrative Review & Approval</h2>
                    <p>
                        Our nursery management team inspects each submitted request. Upon evaluation:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-700">
                        <li><strong>Approved Returns:</strong> The request is approved, and our team coordinates the return logistics or pickup.</li>
                        <li><strong>Rejected Returns:</strong> If a request does not meet eligibility requirements, our administrators provide an explicit recorded explanation in the system.</li>
                        <li><strong>Physical Completion:</strong> Once returned items are physically received at the nursery center, the return status is marked as completed.</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">5. Secure Refund Processing</h2>
                    <p>
                        Refunds are executed securely and server-side through our integrated payment gateway (Razorpay / original payment instrument):
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-slate-700">
                        <li>Refunds are credited directly back to the original payment method used during checkout.</li>
                        <li>Both full and partial refunds are supported based on approved items and quantities.</li>
                        <li>Refund amounts are cryptographically validated to guarantee they never exceed the remaining refundable transaction balance.</li>
                        <li>Depending on your banking partner or card issuer, funds typically reflect in your account within standard banking turnaround timelines (typically 5–7 business days).</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">6. Historical Invoices</h2>
                    <p>
                        Original tax invoices remain immutable historical legal documents. In accordance with accounting standards, invoices remain accessible in your account dashboard regardless of subsequent return or refund operations.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">7. Need Assistance?</h2>
                    <p>
                        If you need any guidance submitting a return or have questions regarding an existing refund, reach out to our team:
                    </p>
                    <div class="text-xs text-slate-600 bg-slate-100/80 p-4 rounded-lg space-y-1">
                        <p><strong>Nursery Center:</strong> Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082</p>
                        <p><strong>Phone Support:</strong> 098111 14365</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
