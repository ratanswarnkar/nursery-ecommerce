@extends('layouts.storefront')

@section('seo')
    <title>{{ $seoData['title'] ?? 'The Botanical Haven | Premium Indoor & Garden Plants' }}</title>
    <meta name="description" content="{{ $seoData['description'] ?? 'Discover flourishing indoor plants, flowering perennials, ceramic planters, and organic gardening essentials.' }}">
    <link rel="canonical" href="{{ $seoData['canonical'] ?? route('home') }}">
    <meta property="og:title" content="{{ $seoData['title'] ?? 'The Botanical Haven' }}">
    <meta property="og:description" content="{{ $seoData['description'] ?? 'Cultivating green life with care.' }}">
    <meta property="og:url" content="{{ $seoData['canonical'] ?? route('home') }}">
    <meta property="og:type" content="website">
    @if(!empty($seoData['schema']))
        {!! $seoData['schema'] !!}
    @endif
@endsection

@section('content')
<div class="space-y-16 sm:space-y-24">
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-emerald-950 via-emerald-900 to-forest-900 text-white rounded-3xl p-8 sm:p-14 lg:p-20 shadow-2xl border border-emerald-800/60">
        {{-- Decorative Botanical Background Shapes --}}
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-emerald-700/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-terracotta-600/15 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-2xl space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-800/80 border border-emerald-600/40 text-emerald-200 text-xs font-semibold tracking-wide uppercase">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Fresh Nursery Stock & Rare Botanicals
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-stone-50 leading-[1.15]">
                Bring Living Greenery <br class="hidden sm:inline">Into Your Everyday Space.
            </h1>

            <p class="text-base sm:text-lg text-emerald-100/90 leading-relaxed font-normal max-w-xl">
                Nurtured with expert care in sustainable soil blends. Explore acclimatized houseplants, blooming saplings, handcrafted terracotta, and natural plant nutrition.
            </p>

            <div class="pt-2 flex flex-wrap items-center gap-4">
                <a href="{{ route('shop.index') }}"
                   class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-500 text-white text-sm font-bold shadow-lg shadow-terracotta-900/40 hover:-translate-y-0.5 transition-all">
                    <span>Explore All Plants</span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
                <a href="#featured"
                   class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-stone-100 text-sm font-semibold backdrop-blur-sm border border-white/20 hover:-translate-y-0.5 transition-all">
                    <span>Featured Specimens</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Trust Features --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm">
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-stone-900 text-sm">Transit Guarantee</h3>
                <p class="text-xs text-stone-500 mt-1">Healthy root arrivals secured with eco-cushioning and moisture barriers.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm">
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-stone-900 text-sm">Botanist Care Cards</h3>
                <p class="text-xs text-stone-500 mt-1">Every specimen includes customized sunlight, watering & feeding guidelines.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm">
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-stone-900 text-sm">100% Peat-Reduced</h3>
                <p class="text-xs text-stone-500 mt-1">Eco-friendly coconut coir, leaf mold, and enriched organic bio-compost.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm">
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-stone-900 text-sm">Nursery Horticulturists</h3>
                <p class="text-xs text-stone-500 mt-1">Direct support and plant diagnostics from our experienced grower staff.</p>
            </div>
        </div>
    </section>

    {{-- Popular Plant Categories --}}
    @if($rootCategories->isNotEmpty())
        <section class="space-y-6">
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Botanical Collections</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-stone-900 mt-1">Explore by Plant Family</h2>
                </div>
                <a href="{{ route('shop.index') }}" class="text-xs sm:text-sm font-semibold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 group">
                    <span>View all</span>
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($rootCategories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}"
                       class="group relative flex flex-col items-center p-5 rounded-2xl bg-white border border-stone-200/80 hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-900/5 transition-all text-center">
                        <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-800 flex items-center justify-center mb-3 group-hover:bg-emerald-800 group-hover:text-white transition-colors duration-300">
                            <svg class="w-7 h-7 stroke-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-stone-900 text-sm group-hover:text-emerald-800 transition-colors">
                            {{ $category->name }}
                        </h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured Curated Products --}}
    @if($featuredProducts->isNotEmpty())
        <section id="featured" class="space-y-6">
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Hand-Selected</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-stone-900 mt-1">Featured Botanical Highlights</h2>
                </div>
                <a href="{{ route('shop.index', ['sort' => 'featured']) }}" class="text-xs sm:text-sm font-semibold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 group">
                    <span>See All Featured</span>
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Seasonal Saplings / New Arrivals --}}
    @if($newArrivals->isNotEmpty())
        <section class="space-y-6">
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-terracotta-700">Recent Greenhouse Additions</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-stone-900 mt-1">Freshly Propagated & Available</h2>
                </div>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}" class="text-xs sm:text-sm font-semibold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 group">
                    <span>View Latest</span>
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($newArrivals as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Plant Care & Propagation Banner --}}
    <section class="rounded-3xl bg-stone-900 text-stone-100 p-8 sm:p-12 lg:p-16 flex flex-col lg:flex-row items-center justify-between gap-8">
        <div class="max-w-xl space-y-4">
            <span class="text-xs font-bold uppercase tracking-widest text-emerald-400">Green Thumb Knowledge</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight">Every Plant Order Includes Living Growth Guarantee</h2>
            <p class="text-sm sm:text-base text-stone-300 leading-relaxed">
                We believe plant care should be joyful and accessible. Our specimens come with detailed botanical instructions, soil recommendations, and post-delivery nursery guidance.
            </p>
        </div>
        <div class="shrink-0 flex flex-col sm:flex-row gap-4">
            <a href="{{ route('shop.index') }}"
               class="px-6 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm text-center shadow-lg transition-colors">
                Start Your Plant Journey
            </a>
        </div>
    </section>
</div>
@endsection
