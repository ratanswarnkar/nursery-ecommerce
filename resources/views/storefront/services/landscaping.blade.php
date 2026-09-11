@extends('layouts.storefront')

@section('seo')
    <title>{{ $seoData['title'] ?? 'Landscaping Services in Delhi NCR | Sugandha Farms and Nursery' }}</title>
    <meta name="description" content="{{ $seoData['description'] ?? 'Professional landscape design, residential & commercial garden development, lawn installation, and garden maintenance across Delhi NCR by Sugandha Farms and Nursery.' }}">
    <link rel="canonical" href="{{ $seoData['canonical'] ?? route('services.landscaping') }}">
    <meta property="og:title" content="{{ $seoData['title'] ?? 'Landscaping Services in Delhi NCR | Sugandha Farms and Nursery' }}">
    <meta property="og:description" content="{{ $seoData['description'] ?? 'Professional landscape design, residential & commercial garden development across Delhi NCR.' }}">
    <meta property="og:url" content="{{ $seoData['canonical'] ?? route('services.landscaping') }}">
    <meta property="og:type" content="website">
    @if(!empty($seoData['schema']))
        {!! $seoData['schema'] !!}
    @endif
@endsection

@section('content')
<div class="bg-slate-50 min-h-screen py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center text-xs text-slate-500 mb-8 gap-2" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Home</a>
            <span>/</span>
            <span class="text-slate-400">Services</span>
            <span>/</span>
            <span class="text-slate-800 font-semibold" aria-current="page">Landscaping Services</span>
        </nav>

        <!-- Hero Section -->
        <section class="relative rounded-3xl bg-gradient-to-br from-emerald-950 via-slate-900 to-emerald-900 text-white overflow-hidden p-8 sm:p-12 lg:p-16 mb-12 shadow-xl border border-emerald-800/40">
            {{-- Background Botanical Vignette Decor --}}
            <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -top-20 w-80 h-80 bg-green-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 mb-5">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Botanical Service &bull; Delhi NCR
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight font-serif">
                    Professional Landscaping Services in Delhi NCR
                </h1>

                <p class="text-base sm:text-lg text-emerald-100/90 mt-5 leading-relaxed">
                    Transform your outdoor, terrace, and garden spaces with end-to-end botanical landscaping, softscaping, lawn development, and structured green maintenance delivered directly by our nursery horticulturists.
                </p>

                <!-- Key Highlights -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8 pt-8 border-t border-emerald-800/60 text-xs sm:text-sm">
                    <div class="flex items-center gap-2.5 text-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Direct Nursery Specimen Sourcing</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Acclimatized for Delhi NCR Climate</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Organic Soil &amp; Nutrient Blends</span>
                    </div>
                </div>

                <!-- Call to Actions -->
                <div class="flex flex-wrap items-center gap-4 mt-8">
                    <a href="{{ route('policy.contact') }}" 
                       class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-lg shadow-emerald-950/40 transition-all">
                        <span>Request Service Consultation</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                    <a href="tel:09811114365" 
                       class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-sm border border-white/20 backdrop-blur-xs transition">
                        <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>Call 098111 14365</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Service Offerings Grid -->
        <section class="mb-14">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    Comprehensive Green Solutions
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-3 font-serif">
                    Our Landscaping Services
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    Professional, sustainable botanical management from site inspection to mature planting and seasonal garden care.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($serviceOfferings as $service)
                    <div class="bg-white rounded-2xl p-7 border border-slate-200/80 shadow-xs hover:shadow-md hover:border-emerald-300 transition-all flex flex-col justify-between group">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-5 group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-200">
                                @if($service['icon'] === 'garden')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582"/>
                                    </svg>
                                @elseif($service['icon'] === 'residential')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                @elseif($service['icon'] === 'commercial')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                @elseif($service['icon'] === 'lawn')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                    </svg>
                                @elseif($service['icon'] === 'planting')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.828 2.828a1 1 0 01-1.414 0L3 12.172a1 1 0 010-1.414l4.95-4.95a1 1 0 011.414 0L12 8.586"/>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">
                                {{ $service['title'] }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 mt-2.5 leading-relaxed">
                                {{ $service['description'] }}
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-emerald-700 font-semibold">
                            <span>Available in Delhi NCR</span>
                            <a href="{{ route('policy.contact') }}" class="hover:underline flex items-center gap-1">
                                <span>Inquire</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Our Approach Section -->
        <section class="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200/80 shadow-xs mb-14">
            <div class="max-w-3xl mb-10">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Horticultural Workflow</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2 font-serif">
                    How We Deliver Botanical Projects
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    Every project is overseen by skilled nursery technicians ensuring optimal soil preparation, sunlight adaptation, and long-term vitality.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center mb-3">1</span>
                    <h3 class="font-bold text-slate-900 text-sm">Site &amp; Sunlight Review</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        Detailed evaluation of space dimensions, daily sunlight exposure, drainage slopes, and intended aesthetic function.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center mb-3">2</span>
                    <h3 class="font-bold text-slate-900 text-sm">Botanical Selection</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        Selection of robust, acclimatized specimens, flowering shrubs, and shade trees proven to thrive in Delhi NCR conditions.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center mb-3">3</span>
                    <h3 class="font-bold text-slate-900 text-sm">Soil Prep &amp; Planting</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        Deep soil aeration, incorporation of organic compost and neem cake, root-safe plantation, and mulch bedding.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center mb-3">4</span>
                    <h3 class="font-bold text-slate-900 text-sm">Ongoing Maintenance</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        Structured schedule for irrigation tuning, organic bio-nutrition, seasonal pruning, and proactive foliage health checks.
                    </p>
                </div>
            </div>
        </section>

        <!-- Bottom Consultation CTA -->
        <section class="rounded-3xl bg-emerald-900 text-white p-8 sm:p-12 text-center relative overflow-hidden">
            <div class="max-w-2xl mx-auto relative z-10">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Ready to Green Your Space?</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white mt-2 font-serif">
                    Plan Your Landscaping Project with Our Nursery Team
                </h2>
                <p class="text-sm text-emerald-100/90 mt-3 leading-relaxed">
                    Whether you are developing a residential lawn, a terrace garden, or commercial property grounds, we provide personalized botanical guidance across Delhi NCR.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4 mt-7">
                    <a href="{{ route('policy.contact') }}" 
                       class="px-6 py-3 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold text-sm transition">
                        Inquire via Contact Form
                    </a>
                    <a href="tel:09811114365" 
                       class="px-6 py-3 rounded-xl bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-sm border border-emerald-700 transition">
                        Call Our Nursery: 098111 14365
                    </a>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection
