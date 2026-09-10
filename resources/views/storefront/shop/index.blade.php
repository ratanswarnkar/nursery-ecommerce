@extends('layouts.storefront')

@section('seo')
    <title>{{ $seoData['title'] ?? 'Shop All Plants & Nursery Botanicals | Sugandha Farms and Nursery' }}</title>
    <meta name="description" content="{{ $seoData['description'] ?? 'Browse our complete nursery catalog of healthy, acclimatized plants, saplings, and garden supplies.' }}">
    <link rel="canonical" href="{{ $seoData['canonical'] ?? route('shop.index') }}">
    <meta property="og:title" content="{{ $seoData['title'] ?? 'Shop Botanical Catalog' }}">
    <meta property="og:description" content="{{ $seoData['description'] ?? 'Explore our botanical collections.' }}">
    <meta property="og:url" content="{{ $seoData['canonical'] ?? route('shop.index') }}">
    @if(!empty($seoData['robots']))
        <meta name="robots" content="{{ $seoData['robots'] }}">
    @endif
    @if(!empty($seoData['schema']))
        {!! $seoData['schema'] !!}
    @endif
@endsection

@section('content')
<div class="space-y-6" x-data="{ mobileFiltersOpen: false }">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Catalog / Shop', 'url' => route('shop.index')]
    ]" />

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-stone-200">
        <div>
            <h1 class="text-3xl font-extrabold text-stone-900 tracking-tight">Plant Catalog</h1>
            <p class="text-sm text-stone-500 mt-1">
                Showing <span class="font-semibold text-stone-800">{{ $products->total() }}</span> botanical specimens
                @if(!empty($filters['q']))
                    matching &ldquo;<span class="font-semibold text-emerald-800">{{ $filters['q'] }}</span>&rdquo;
                @endif
            </p>
        </div>

        {{-- Top Bar Controls: Mobile Filter Button & Sort Selector --}}
        <div class="flex items-center gap-3 self-end md:self-auto w-full md:w-auto justify-between md:justify-end">
            {{-- Mobile Filter Toggle --}}
            <button type="button"
                    @click="mobileFiltersOpen = true"
                    class="lg:hidden inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-stone-300 text-stone-700 text-xs font-semibold shadow-sm hover:bg-stone-50">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <span>Filter ({{ count($filters['attributes'] ?? []) + count((array)($filters['brand'] ?? [])) + (!empty($filters['category']) ? 1 : 0) + (!empty($filters['in_stock']) ? 1 : 0) + (!empty($filters['min_price']) || !empty($filters['max_price']) ? 1 : 0) }})</span>
            </button>

            {{-- Sort By Selector --}}
            <div class="flex items-center gap-2">
                <label for="catalog-sort" class="text-xs font-medium text-stone-500 shrink-0">Sort By:</label>
                <select id="catalog-sort"
                        onchange="const url = new URL(window.location.href); url.searchParams.set('sort', this.value); url.searchParams.delete('page'); window.location.href = url.toString();"
                        class="text-xs rounded-xl border border-stone-300 bg-white py-2 pl-3 pr-8 font-medium text-stone-800 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                    <option value="featured" {{ ($filters['sort'] ?? 'featured') === 'featured' ? 'selected' : '' }}>Featured</option>
                    <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Main Catalog Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        {{-- Desktop Filter Sidebar --}}
        <aside class="hidden lg:block lg:col-span-1 bg-white p-5 rounded-2xl border border-stone-200/80 shadow-sm sticky top-24">
            <x-storefront.filter-sidebar
                :facets="$facets"
                :currentFilters="$filters"
                :actionUrl="route('shop.index')" />
        </aside>

        {{-- Mobile Filter Drawer Modal --}}
        <div x-show="mobileFiltersOpen"
             x-cloak
             class="relative z-50 lg:hidden"
             role="dialog"
             aria-modal="true">
            <div x-show="mobileFiltersOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileFiltersOpen = false"
                 class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs"></div>

            <div class="fixed inset-0 z-50 flex">
                <div x-show="mobileFiltersOpen"
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-auto flex h-full w-full max-w-xs flex-col overflow-y-auto bg-white py-4 pb-12 shadow-xl p-6">
                    <div class="flex items-center justify-between border-b border-stone-200 pb-4 mb-4">
                        <h2 class="text-base font-bold text-stone-900">Filters</h2>
                        <button type="button" @click="mobileFiltersOpen = false" class="text-stone-400 hover:text-stone-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <x-storefront.filter-sidebar
                        :facets="$facets"
                        :currentFilters="$filters"
                        :actionUrl="route('shop.index')" />
                </div>
            </div>
        </div>

        {{-- Product Grid --}}
        <main class="lg:col-span-3 space-y-8">
            @if($products->isEmpty())
                <div class="p-8 sm:p-12 text-center bg-white rounded-3xl border border-stone-200/80 shadow-sm space-y-5">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center border border-emerald-100 shadow-inner">
                        <svg class="w-8 h-8 stroke-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div class="space-y-2">
                        @if(!empty($filters['q']))
                            <h2 class="text-xl sm:text-2xl font-bold text-stone-900">
                                No products found matching &ldquo;{{ $filters['q'] }}&rdquo;
                            </h2>
                        @else
                            <h2 class="text-xl sm:text-2xl font-bold text-stone-900">
                                No Botanical Specimens Found
                            </h2>
                        @endif
                        <p class="text-sm text-stone-600 max-w-md mx-auto">
                            We couldn't find any botanical plants matching your active criteria.
                        </p>
                    </div>

                    {{-- Helpful Suggestions --}}
                    <div class="max-w-md mx-auto bg-stone-50 rounded-2xl p-4 border border-stone-200/70 text-left text-xs sm:text-sm text-stone-600 space-y-2">
                        <span class="font-semibold text-stone-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Suggestions to help find your plant:
                        </span>
                        <ul class="list-disc list-inside space-y-1 text-stone-500 pl-1 text-xs">
                            <li>Check for typos or spelling errors in your search terms</li>
                            <li>Try using broader or alternate plant names (e.g. &ldquo;Ficus&rdquo; instead of specific variety)</li>
                            <li>Clear some active filters such as specific brand, category, or price range</li>
                            <li>Browse our complete nursery catalog to view all available plants</li>
                        </ul>
                    </div>

                    {{-- Action CTAs --}}
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('shop.index') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-semibold shadow-sm transition-all">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Clear All Filters &amp; Search
                        </a>
                        <a href="{{ route('shop.index') }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-semibold transition-all">
                            Browse All Plants
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    @foreach($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="pt-6 border-t border-stone-200">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </main>
    </div>
</div>
@endsection
