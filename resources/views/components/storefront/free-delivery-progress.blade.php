@props([
    'subtotal' => '0.00'
])

@php
    $subtotalFloat = max(0.0, (float) $subtotal);
    $subtotalStr = number_format($subtotalFloat, 2, '.', '');
    $threshold = '1000.00';
    $comp = bccomp($subtotalStr, $threshold, 2);

    $isAbove = $comp > 0;
    $isExact = $comp === 0;
    $isBelow = $comp < 0;

    if ($isAbove) {
        $progressPercent = 100;
        $remaining = '0.00';
    } elseif ($isExact) {
        $progressPercent = 99;
        $remaining = '0.01';
    } else {
        $remaining = bcsub($threshold, $subtotalStr, 2);
        $percentRaw = ($subtotalFloat / 1000.0) * 100.0;
        $progressPercent = (int) max(5, min(95, round($percentRaw)));
    }
@endphp

<div class="w-full rounded-2xl p-4 sm:p-5 border transition-all {{ $isAbove ? 'bg-emerald-50/90 border-emerald-200 shadow-sm' : ($isExact ? 'bg-amber-50/90 border-amber-200 shadow-sm' : 'bg-white border-stone-200/90 shadow-sm') }}"
     data-delivery-progress>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-2.5 min-w-0">
            @if($isAbove)
                <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-emerald-950 flex items-center gap-1.5 flex-wrap">
                        <span>🎉 You qualify for FREE delivery</span>
                    </h3>
                    <p class="text-xs text-emerald-800 font-medium">
                        Delivery within 3 days &bull; Delhi NCR only
                    </p>
                </div>
            @elseif($isExact)
                <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-amber-950">
                        Add a little more to qualify for FREE delivery
                    </h3>
                    <p class="text-xs text-amber-800 font-medium">
                        Orders ABOVE ₹1,000 qualify for free delivery &bull; Delhi NCR only &bull; Delivery within 3 days
                    </p>
                </div>
            @else
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-stone-900">
                        Add ₹{{ number_format((float) $remaining, 2) }} more to get FREE delivery
                    </h3>
                    <p class="text-xs text-stone-600 font-medium">
                        Orders ABOVE ₹1,000 qualify for free delivery &bull; Delhi NCR only &bull; Delivery within 3 days
                    </p>
                </div>
            @endif
        </div>

        <div class="shrink-0 flex items-center gap-2">
            @if($isAbove)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-xs">
                    Free Delivery Qualified
                </span>
            @elseif($isExact)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-200 text-amber-900">
                    Threshold: Above ₹1,000
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-stone-100 text-stone-700">
                    {{ $progressPercent }}% towards free delivery
                </span>
            @endif
        </div>
    </div>

    {{-- Accessible Progress Bar --}}
    <div class="w-full bg-stone-200/80 rounded-full h-2.5 overflow-hidden"
         role="progressbar"
         aria-valuenow="{{ $progressPercent }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="{{ $isAbove ? 'Free delivery achieved' : ($isExact ? 'Subtotal at threshold, requires amount strictly above 1,000 rupees' : 'Free delivery progress: ' . $progressPercent . ' percent') }}">
        <div class="h-2.5 rounded-full transition-all duration-500 ease-out {{ $isAbove ? 'bg-emerald-600' : ($isExact ? 'bg-amber-500' : 'bg-emerald-700') }}"
             style="width: {{ $progressPercent }}%;"></div>
    </div>
</div>
