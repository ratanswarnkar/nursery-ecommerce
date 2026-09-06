@props(['product'])

@php
    $primaryImage = $product->primaryImage ?? $product->images?->first();
    $imageUrl = $primaryImage ? $primaryImage->url : null;
    $defaultVariant = $product->defaultVariant ?? $product->variants?->where('is_active', true)->first();
    $hasDiscount = $defaultVariant && $defaultVariant->compare_at_price && ((float)$defaultVariant->compare_at_price > (float)$defaultVariant->price);
    $discountPercent = $hasDiscount ? round((((float)$defaultVariant->compare_at_price - (float)$defaultVariant->price) / (float)$defaultVariant->compare_at_price) * 100) : 0;
    $brandName = $product->brand?->name;
    $inStock = $product->has_stock;
@endphp

<div class="group relative flex flex-col bg-white rounded-2xl border border-stone-200/80 hover:border-emerald-300 hover:shadow-xl hover:shadow-emerald-950/5 transition-all duration-300 overflow-hidden">
    {{-- Image Container --}}
    <div class="relative aspect-square w-full bg-stone-100 overflow-hidden">
        <a href="{{ route('products.show', $product->slug) }}" class="block w-full h-full">
            @if($imageUrl)
                <img src="{{ $imageUrl }}"
                     alt="{{ $primaryImage->alt_text ?? $product->name }}"
                     loading="lazy"
                     class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500 ease-out">
            @else
                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-stone-100 to-emerald-50/40 text-stone-400 group-hover:text-emerald-600 transition-colors">
                    <svg class="w-16 h-16 stroke-1 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                    </svg>
                    <span class="text-xs font-medium text-stone-400">Botanical Specimen</span>
                </div>
            @endif
        </a>

        {{-- Badges --}}
        <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
            <div class="flex flex-col gap-1">
                @if($hasDiscount)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-terracotta-600 text-white shadow-sm">
                        {{ $discountPercent }}% OFF
                    </span>
                @endif
                @if($product->is_featured)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-700 text-white shadow-sm">
                        Curated
                    </span>
                @endif
            </div>

            <div class="ml-auto">
                @if(!$inStock)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-900/80 text-white backdrop-blur-sm">
                        Sold Out
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex flex-col flex-grow p-4">
        {{-- Brand / Category Meta --}}
        <div class="flex items-center justify-between text-xs text-stone-500 mb-1">
            @if($brandName)
                <span class="font-medium text-stone-600 uppercase tracking-wider text-[10px]">{{ $brandName }}</span>
            @elseif($product->categories->isNotEmpty())
                <span class="font-medium text-emerald-800 tracking-wide text-[11px]">{{ $product->categories->first()->name }}</span>
            @else
                <span class="text-[10px] text-stone-400 uppercase tracking-wider">Plant Life</span>
            @endif
        </div>

        {{-- Title --}}
        <h3 class="font-semibold text-stone-900 text-sm sm:text-base leading-snug mb-1.5 group-hover:text-emerald-700 transition-colors line-clamp-2">
            <a href="{{ route('products.show', $product->slug) }}">
                {{ $product->name }}
            </a>
        </h3>

        {{-- Short Description (if present) --}}
        @if($product->short_description)
            <p class="text-xs text-stone-500 line-clamp-1 mb-3">
                {{ $product->short_description }}
            </p>
        @else
            <div class="mb-2"></div>
        @endif

        {{-- Price & CTA Row --}}
        <div class="mt-auto pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
            <div>
                @if($product->min_price !== null)
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xs text-stone-500 font-normal">from</span>
                        <span class="text-base sm:text-lg font-bold text-emerald-950 tracking-tight">
                            ₹{{ number_format((float)$product->min_price, 2) }}
                        </span>
                    </div>
                @else
                    <span class="text-sm font-semibold text-stone-500">Contact for Price</span>
                @endif
            </div>

            <a href="{{ route('products.show', $product->slug) }}"
               class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-700 hover:text-white transition-colors duration-200">
                <span>View</span>
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>
