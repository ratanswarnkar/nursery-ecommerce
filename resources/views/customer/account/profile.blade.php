@extends('layouts.storefront')

@section('seo')
    <title>Customer Profile | Sugandha Farms and Nursery</title>
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Account', 'url' => route('account.dashboard')],
        ['name' => 'Personal Profile', 'url' => '']
    ]" />

    <div class="bg-white rounded-3xl border border-stone-200/80 shadow-sm p-6 sm:p-10 space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-900 tracking-tight">Customer Profile</h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">
                Manage your customer details for order receipts and communication.
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <div>&bull; {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Full Name --}}
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1.5">
                    Full Name <span class="text-rose-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $customer->name) }}"
                       required
                       maxlength="100"
                       class="w-full px-4 py-2.5 rounded-xl border border-stone-300 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
            </div>

            {{-- Registered Phone (Read-Only Identity) --}}
            <div>
                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1.5">
                    Mobile Phone (Login Identifier)
                </label>
                <input type="text"
                       id="phone"
                       value="{{ $customer->phone }}"
                       disabled
                       class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-100 text-stone-500 text-sm font-mono cursor-not-allowed">
                <p class="text-[11px] text-stone-400 mt-1">
                    Phone number is verified via OTP and serves as your secure account credential.
                </p>
            </div>

            {{-- Email Address --}}
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1.5">
                    Email Address (Optional)
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email', $customer->email) }}"
                       maxlength="150"
                       placeholder="you@example.com"
                       class="w-full px-4 py-2.5 rounded-xl border border-stone-300 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                <p class="text-[11px] text-stone-400 mt-1">
                    Used for optional order invoices and dispatch notifications.
                </p>
            </div>

            <div class="pt-4 border-t border-stone-100 flex items-center justify-between">
                <a href="{{ route('account.dashboard') }}" class="text-xs font-semibold text-stone-500 hover:text-stone-700">
                    &larr; Back to Dashboard
                </a>

                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs shadow-md transition-colors">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
