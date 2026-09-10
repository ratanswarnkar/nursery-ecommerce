@extends('layouts.storefront')

@section('seo')
    <title>{{ $seoData['title'] ?? 'Sugandha Farms and Nursery | Wholesale Plant Nursery & Living Greenery' }}</title>
    <meta name="description" content="{{ $seoData['description'] ?? 'Discover flourishing indoor plants, flowering perennials, ceramic planters, and organic gardening essentials.' }}">
    <link rel="canonical" href="{{ $seoData['canonical'] ?? route('home') }}">
    <meta property="og:title" content="{{ $seoData['title'] ?? 'Sugandha Farms and Nursery' }}">
    <meta property="og:description" content="{{ $seoData['description'] ?? 'Cultivating green life with care.' }}">
    <meta property="og:url" content="{{ $seoData['canonical'] ?? route('home') }}">
    <meta property="og:type" content="website">
    @if(!empty($seoData['schema']))
        {!! $seoData['schema'] !!}
    @endif
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-16 sm:space-y-24">
    {{-- Hero Section --}}
    <section class="storefront-hero relative overflow-hidden bg-gradient-to-b from-emerald-950 via-emerald-900 to-forest-900 text-white rounded-3xl p-5 sm:p-8 lg:p-10 xl:p-12 shadow-2xl border border-emerald-800/60">
        {{-- Decorative Botanical Background Shapes --}}
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-emerald-700/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-terracotta-600/15 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 xl:gap-12 items-center">
            {{-- Left Side: Botanical Copy & CTAs (approx. 50-55%) --}}
            <div class="lg:col-span-7 xl:col-span-6 min-w-0 space-y-5 sm:space-y-6">
                <div class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full bg-emerald-800/80 border border-emerald-600/40 text-emerald-200 text-[10px] sm:text-xs font-semibold tracking-wider sm:tracking-wide uppercase max-w-full">
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span class="truncate sm:overflow-visible">Fresh Nursery Stock & Rare Botanicals</span>
                </div>

                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-stone-50 leading-tight sm:leading-[1.18]">
                    Bring Living Greenery <br class="hidden sm:inline">Into Your Everyday Space.
                </h1>

                <p class="text-sm sm:text-base lg:text-lg text-emerald-100/90 leading-relaxed font-normal max-w-xl">
                    Nurtured with expert care in sustainable soil blends. Explore acclimatized houseplants, blooming saplings, handcrafted terracotta, and natural plant nutrition.
                </p>

                <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
                    <a href="{{ route('shop.index') }}"
                       class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-500 text-white text-sm font-bold shadow-lg shadow-terracotta-900/40 hover:-translate-y-0.5 transition-all text-center">
                        <span>Explore All Plants</span>
                        <svg class="w-4 h-4 ml-2" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    <a href="#featured"
                       class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-stone-100 text-sm font-semibold backdrop-blur-sm border border-white/20 hover:-translate-y-0.5 transition-all text-center">
                        <span>Featured Specimens</span>
                    </a>
                </div>
            </div>

            {{-- Right Side: Botanical Nursery Visual Showcase (approx. 45-50%) --}}
            <div class="lg:col-span-5 xl:col-span-6 min-w-0 w-full flex justify-center lg:justify-end">
                <div class="relative w-full max-w-lg lg:max-w-none">
                    {{-- Subtle Ambient Glow Behind Image Frame --}}
                    <div class="absolute -inset-1.5 bg-gradient-to-tr from-emerald-600/30 via-emerald-500/15 to-terracotta-500/20 rounded-3xl blur-xl opacity-75 pointer-events-none"></div>

                    {{-- Image Container Frame with Rounded Corners & Soft Botanical Border --}}
                    <div class="relative rounded-2xl sm:rounded-3xl overflow-hidden border border-emerald-700/60 shadow-2xl shadow-emerald-950/70 ring-1 ring-white/15 bg-emerald-950/40">
                        <img src="{{ asset('images/hero-botanical-plants.jpg') }}"
                             alt="Healthy indoor houseplants and lush green foliage in artisanal ceramic and terracotta planters"
                             class="w-full h-56 sm:h-72 md:h-80 lg:h-[380px] xl:h-[420px] object-cover object-center transform transition-transform duration-700 hover:scale-[1.02]"
                             loading="eager"
                             fetchpriority="high"
                             width="800"
                             height="600">

                        {{-- Subtle Botanical Vignette Gradient Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-emerald-950/40 via-transparent to-transparent pointer-events-none"></div>
                        <div class="hidden lg:block absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-emerald-950/40 to-transparent pointer-events-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust Features --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="flex items-start gap-4 p-5 rounded-2xl bg-white border border-stone-200/80 shadow-sm">
            <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 shrink-0">
                <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($rootCategories as $category)
                    @php
                        $slug = strtolower($category->slug);
                        $isPlanter = str_contains($slug, 'planter') || str_contains($slug, 'pot') || str_contains($slug, 'terracotta');
                        $isFlower = str_contains($slug, 'flower') || str_contains($slug, 'bloom');
                        $isAir = str_contains($slug, 'air') || str_contains($slug, 'purif');
                        $isCare = str_contains($slug, 'care') || str_contains($slug, 'soil') || str_contains($slug, 'nutri');
                    @endphp
                    <a href="{{ route('categories.show', $category->slug) }}"
                       class="group relative flex flex-col items-center p-5 rounded-2xl bg-white border border-stone-200/80 hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-900/5 transition-all text-center">
                        <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-800 flex items-center justify-center mb-3 group-hover:bg-emerald-800 group-hover:text-white transition-colors duration-300">
                            @if($isPlanter)
                                <svg class="w-7 h-7 stroke-1.5" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            @elseif($isFlower)
                                <svg class="w-7 h-7 stroke-1.5" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($isAir)
                                <svg class="w-7 h-7 stroke-1.5" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($isCare)
                                <svg class="w-7 h-7 stroke-1.5" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            @else
                                <svg class="w-7 h-7 stroke-1.5" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                                </svg>
                            @endif
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
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
