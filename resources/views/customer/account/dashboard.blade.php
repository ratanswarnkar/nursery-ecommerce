@extends('layouts.storefront')

@section('seo')
    <title>Customer Account Dashboard | Sugandha Farms and Nursery</title>
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="space-y-8">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Account', 'url' => route('account.dashboard')],
        ['name' => 'Dashboard', 'url' => '']
    ]" />

    {{-- Account Header --}}
    <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-950 via-emerald-900 to-forest-900 text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center text-2xl font-bold text-emerald-200 shrink-0">
                {{ substr($customer->name ?: $customer->phone, 0, 1) }}
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Welcome, {{ $customer->name ?: 'Botanical Enthusiast' }}
                </h1>
                <p class="text-xs sm:text-sm text-emerald-200/80 mt-1 font-mono">
                    {{ $customer->phone }} &bull;
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-700/80 text-emerald-100">
                        Active Customer
                    </span>
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('customer.logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/20 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Log Out</span>
            </button>
        </form>
    </div>

    {{-- Navigation Cards / Shortcuts --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Profile Shortcut --}}
        <a href="{{ route('account.profile') }}"
           class="group p-6 rounded-3xl bg-white border border-stone-200/80 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-900/5 transition-all">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center mb-4 group-hover:bg-emerald-800 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h3 class="font-bold text-stone-900 text-lg group-hover:text-emerald-800 transition-colors">Personal Profile</h3>
            <p class="text-xs text-stone-500 mt-1">Manage your full name, email notifications, and registered phone.</p>
            <span class="inline-flex items-center text-xs font-semibold text-emerald-800 mt-4 group-hover:translate-x-1 transition-transform">
                Edit Profile &rarr;
            </span>
        </a>

        {{-- Address Shortcut --}}
        <a href="{{ route('account.addresses.index') }}"
           class="group p-6 rounded-3xl bg-white border border-stone-200/80 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-900/5 transition-all">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center mb-4 group-hover:bg-emerald-800 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="font-bold text-stone-900 text-lg group-hover:text-emerald-800 transition-colors">Address Book</h3>
            <p class="text-xs text-stone-500 mt-1">
                @if($defaultAddress)
                    Default: {{ $defaultAddress->city }}, {{ $defaultAddress->state }}
                @else
                    No delivery address registered yet.
                @endif
            </p>
            <span class="inline-flex items-center text-xs font-semibold text-emerald-800 mt-4 group-hover:translate-x-1 transition-transform">
                Manage Addresses &rarr;
            </span>
        </a>

        {{-- Cart Shortcut --}}
        <a href="{{ route('cart.index') }}"
           class="group p-6 rounded-3xl bg-white border border-stone-200/80 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-900/5 transition-all">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center mb-4 group-hover:bg-emerald-800 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h3 class="font-bold text-stone-900 text-lg group-hover:text-emerald-800 transition-colors">Shopping Cart</h3>
            <p class="text-xs text-stone-500 mt-1">{{ $cartCount }} botanical {{ Str::plural('specimen', $cartCount) }} currently in cart.</p>
            <span class="inline-flex items-center text-xs font-semibold text-emerald-800 mt-4 group-hover:translate-x-1 transition-transform">
                View Cart &rarr;
            </span>
        </a>
    </div>

    {{-- Order History Placeholder (Strictly Phase 5 Compliant) --}}
    <div class="bg-white rounded-3xl border border-stone-200/80 shadow-sm p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-stone-100 pb-4">
            <div>
                <h2 class="text-xl font-bold text-stone-900">Recent Plant Orders</h2>
                <p class="text-xs text-stone-500 mt-0.5">Track shipment and delivery status of your nursery plants.</p>
            </div>
        </div>

        <div class="p-12 text-center rounded-2xl bg-stone-50 border border-dashed border-stone-200 space-y-3">
            <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-stone-700">No orders placed yet</h3>
            <p class="text-xs text-stone-500 max-w-sm mx-auto">
                Once you complete purchases during Phase 6 checkout, your order invoices and tracking will appear here.
            </p>
            <div class="pt-2">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-800 text-white text-xs font-bold hover:bg-emerald-900 transition-colors">
                    Start Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
