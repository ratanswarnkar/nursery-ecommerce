@props(['available' => 0, 'lowStockThreshold' => 5])

@if($available > $lowStockThreshold)
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span>
        In Stock
    </span>
@elseif($available > 0)
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        Only {{ $available }} left
    </span>
@else
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-500 border border-stone-200">
        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-stone-400"></span>
        Out of Stock
    </span>
@endif
