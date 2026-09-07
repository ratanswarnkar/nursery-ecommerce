@extends('layouts.storefront')

@section('seo')
    <title>Customer Login | Sugandha Farms and Nursery</title>
    <meta name="description" content="Log in to your Sugandha Farms and Nursery customer account using your mobile phone number.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="max-w-md mx-auto py-8 sm:py-12">
    <div class="bg-white rounded-3xl border border-stone-200/80 shadow-xl p-8 sm:p-10 space-y-6">
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-stone-900 tracking-tight">Customer Login</h1>
            <p class="text-xs sm:text-sm text-stone-500">
                Secure passwordless login via Mobile OTP
            </p>
        </div>

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(session('otp_requested'))
            <form method="POST" action="{{ route('customer.otp.verify') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="phone" value="{{ session('phone') }}">
                <div>
                    <label for="otp" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1.5">
                        Enter 6-Digit Verification Code
                    </label>
                    <input type="text"
                           id="otp"
                           name="otp"
                           placeholder="123456"
                           maxlength="6"
                           required
                           autofocus
                           class="w-full px-4 py-3 rounded-xl border border-stone-300 text-center tracking-widest text-lg font-mono font-bold focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm shadow-md transition-colors">
                    Verify & Login
                </button>
            </form>

            <div class="pt-2 text-center">
                <form method="POST" action="{{ route('customer.otp.request') }}">
                    @csrf
                    <input type="hidden" name="phone" value="{{ session('phone') }}">
                    <button type="submit" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 transition-colors">
                        Didn't receive code? Resend Code
                    </button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('customer.otp.request') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1.5">
                        Mobile Phone Number
                    </label>
                    <input type="text"
                           id="phone"
                           name="phone"
                           placeholder="+91 9876543210"
                           value="{{ old('phone') }}"
                           required
                           autofocus
                           class="w-full px-4 py-3 rounded-xl border border-stone-300 text-sm focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                    <p class="text-[11px] text-stone-400 mt-1">Enter your 10-digit Indian mobile number</p>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm shadow-md transition-colors">
                    Request OTP
                </button>
            </form>
        @endif

        <div class="pt-4 border-t border-stone-100 text-center text-xs text-stone-400">
            By logging in, you agree to our Terms of Nursery Service and Botanical Guarantee.
        </div>
    </div>
</div>
@endsection
