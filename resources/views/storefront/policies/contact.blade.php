@extends('layouts.storefront')

@section('title', 'Contact Us | Sugandha Farms and Nursery')
@section('meta_description', 'Get in touch with Sugandha Farms and Nursery for plant orders, delivery status, returns, and wholesale nursery inquiries in Delhi NCR.')

@section('content')
<div class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-xs text-slate-500 mb-6 gap-2">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Contact Us</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-12">
            <div class="border-b border-slate-100 pb-6 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Nursery Support
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Contact Sugandha Farms and Nursery</h1>
                <p class="text-sm text-slate-500 mt-2">We are here to support your botanical orders and green spaces.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Card 1 -->
                <div class="bg-emerald-50/40 border border-emerald-200/60 rounded-xl p-6">
                    <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Physical Nursery Location</h3>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        {{ $business['address'] ?? 'Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082' }}
                    </p>
                    <p class="text-xs text-emerald-800 font-semibold mt-3">Serving all areas across Delhi NCR</p>
                </div>

                <!-- Contact Card 2 -->
                <div class="bg-emerald-50/40 border border-emerald-200/60 rounded-xl p-6">
                    <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Phone Support</h3>
                    <p class="text-sm text-slate-600 mt-2">
                        For order updates, plant care assistance, or returns guidance:
                    </p>
                    <p class="text-base font-bold text-emerald-700 mt-3">
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $business['phone'] ?? '09811114365') }}" class="hover:underline">
                            {{ $business['phone'] ?? '098111 14365' }}
                        </a>
                    </p>
                </div>
            </div>

            <!-- Quick Operational Reference -->
            <div class="mt-8 border-t border-slate-100 pt-8">
                <h3 class="text-base font-bold text-slate-900 mb-4">Frequently Referenced Fulfilment Rules</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="block font-bold text-slate-800">Coverage</span>
                        <span class="text-slate-600 mt-1 block">Exclusively within Delhi NCR.</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="block font-bold text-slate-800">Free Delivery</span>
                        <span class="text-slate-600 mt-1 block">On all orders above ₹1,000.</span>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="block font-bold text-slate-800">Delivery Window</span>
                        <span class="text-slate-600 mt-1 block">Completed within 3 days.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
