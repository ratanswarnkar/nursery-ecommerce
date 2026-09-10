@extends('layouts.storefront')

@section('title', 'Shipping & Delivery Policy | Sugandha Farms and Nursery')
@section('meta_description', 'Shipping and delivery policy for Sugandha Farms and Nursery. Serving Delhi NCR with fast botanical deliveries.')

@section('content')
<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-xs text-slate-500 mb-6 gap-2">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Shipping & Delivery Policy</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-12">
            <div class="border-b border-slate-100 pb-6 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Authoritative Fulfilment Terms
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Shipping & Delivery Policy</h1>
                <p class="text-sm text-slate-500 mt-2">Last updated: {{ date('F Y') }} &bull; Sugandha Farms and Nursery</p>
            </div>

            <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed space-y-6">
                <div class="bg-emerald-50/50 border border-emerald-200/70 rounded-xl p-5">
                    <h3 class="text-base font-bold text-emerald-900 mb-2">Key Delivery Highlights</h3>
                    <ul class="list-disc list-inside space-y-1.5 text-emerald-950 font-medium">
                        <li><strong>Exclusive Service Area:</strong> Delivery is available <strong>ONLY within Delhi NCR</strong>.</li>
                        <li><strong>Free Delivery Threshold:</strong> Orders <strong>ABOVE ₹1,000</strong> receive <strong>FREE complimentary delivery</strong>.</li>
                        <li><strong>Standard Fulfilment Timeline:</strong> All confirmed orders are delivered <strong>within 3 days</strong>.</li>
                    </ul>
                </div>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">1. Delivery Coverage Area</h2>
                    <p>
                        Sugandha Farms and Nursery operates specialized climate-controlled nursery logistics. To preserve the health, root hydration, and foliage vitality of living saplings, delivery services are strictly restricted to addresses within the <strong>National Capital Region (Delhi NCR)</strong>. Orders with delivery destinations outside Delhi NCR cannot be serviced.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">2. Delivery Charges & Free Delivery</h2>
                    <p>
                        We offer complimentary delivery on all botanical purchases exceeding ₹1,000. For orders of ₹1,000 or less, a standard nominal delivery fee is calculated and clearly displayed at checkout prior to payment.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">3. Transit Time & Schedule</h2>
                    <p>
                        Orders are carefully picked from our nursery beds and packed using transit-safe root wrapping and eco-friendly structural cartons. Confirmed orders are dispatched and delivered within <strong>3 days</strong> of placement. Customers receive tracking details and status notifications once the package is dispatched and out for delivery.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">4. Safe Transit Handling for Living Plants</h2>
                    <p>
                        Living greenery requires delicate handling. Our nursery dispatch team ensures root balls are adequately watered and stabilized before dispatch. Upon delivery, we recommend unboxing your plants immediately and placing them in indirect ambient sunlight with gentle watering.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-lg font-bold text-slate-900">5. Contact Our Fulfilment Team</h2>
                    <p>
                        For questions regarding your active order delivery, tracking updates, or address coordination, please contact our support desk:
                    </p>
                    <div class="text-xs text-slate-600 bg-slate-100/80 p-4 rounded-lg space-y-1">
                        <p><strong>Nursery Center:</strong> Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082</p>
                        <p><strong>Direct Helpline:</strong> 098111 14365</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
