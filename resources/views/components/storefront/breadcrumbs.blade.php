@props(['breadcrumbs' => []])

@if(!empty($breadcrumbs))
<nav aria-label="Breadcrumb" class="py-3 text-xs sm:text-sm text-stone-500 font-medium">
    <ol class="flex items-center flex-wrap gap-1.5 sm:gap-2">
        <li>
            <a href="{{ route('home') }}" class="flex items-center text-stone-500 hover:text-emerald-700 transition-colors">
                <svg class="w-4 h-4 mr-1 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Home</span>
            </a>
        </li>
        @foreach($breadcrumbs as $breadcrumb)
            <li class="flex items-center gap-1.5 sm:gap-2">
                <svg class="w-3.5 h-3.5 text-stone-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                @if(!empty($breadcrumb['url']) && !$loop->last)
                    <a href="{{ $breadcrumb['url'] }}" class="text-stone-500 hover:text-emerald-700 transition-colors truncate max-w-[150px] sm:max-w-none">
                        {{ $breadcrumb['name'] }}
                    </a>
                @else
                    <span class="text-stone-900 font-semibold truncate max-w-[200px] sm:max-w-none" aria-current="page">
                        {{ $breadcrumb['name'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
