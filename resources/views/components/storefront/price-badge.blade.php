@props(['price', 'compareAtPrice' => null, 'size' => 'md'])

@php
    $formattedPrice = '₹' . number_format((float)$price, 2);
    $hasDiscount = $compareAtPrice && ((float)$compareAtPrice > (float)$price);
    $formattedCompareAt = $hasDiscount ? '₹' . number_format((float)$compareAtPrice, 2) : null;
    $discountPercent = $hasDiscount ? round((((float)$compareAtPrice - (float)$price) / (float)$compareAtPrice) * 100) : 0;
    
    $textSize = match($size) {
        'lg' => 'text-2xl sm:text-3xl font-bold',
        'sm' => 'text-sm font-semibold',
        default => 'text-base sm:text-lg font-bold',
    };
    $compareSize = match($size) {
        'lg' => 'text-base sm:text-lg',
        'sm' => 'text-xs',
        default => 'text-xs sm:text-sm',
    };
@endphp

<div class="flex items-baseline flex-wrap gap-1.5 sm:gap-2">
    <span class="{{ $textSize }} text-emerald-900 tracking-tight">
        {{ $formattedPrice }}
    </span>

    @if($hasDiscount)
        <span class="{{ $compareSize }} text-stone-400 line-through">
            {{ $formattedCompareAt }}
        </span>
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-bold bg-terracotta-50 text-terracotta-700 border border-terracotta-200">
            {{ $discountPercent }}% OFF
        </span>
    @endif
</div>
