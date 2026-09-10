@extends('layouts.storefront')

@section('seo')
    <title>{{ $seoData['title'] ?? ($category->name . ' | Sugandha Farms and Nursery') }}</title>
    <meta name="description" content="{{ $seoData['description'] ?? ($category->description ?: 'Browse ' . $category->name . ' plants.') }}">
    <link rel="canonical" href="{{ $seoData['canonical'] ?? route('categories.show', $category->slug) }}">
    <meta property="og:title" content="{{ $seoData['title'] ?? $category->name }}">
    <meta property="og:description" content="{{ $seoData['description'] ?? $category->description }}">
    <meta property="og:url" content="{{ $seoData['canonical'] ?? route('categories.show', $category->slug) }}">
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
    <x-storefront.breadcrumbs :breadcrumbs="$breadcrumbs ?? [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Shop', 'url' => route('shop.index')],
        ['name' => $category->name, 'url' => '']
    ]" />

    {{-- Category Banner --}}
    <div class="p-6 sm:p-10 rounded-3xl bg-gradient-to-r from-emerald-950 via-emerald-900 to-forest-900 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 max-w-3xl space-y-2">
            <span class="text-xs font-bold uppercase tracking-widest text-emerald-300">Category Collection</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-sm sm:text-base text-emerald-100/90 leading-relaxed pt-1">
                    {{ $category->description }}
                </p>
            @endif
        </div>
    </div>

    {{-- Subcategory Badges (if any) --}}
    @if($subcategories->isNotEmpty())
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <span class="text-xs font-semibold text-stone-500 shrink-0">Subcategories:</span>
            @foreach($subcategories as $sub)
                <a href="{{ route('categories.show', $sub->slug) }}"
                   class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-800 hover:text-white transition-colors border border-emerald-200 shrink-0">
                    {{ $sub->name }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Controls Header --}}
    <div class="flex items-center justify-between pb-4 border-b border-stone-200">
        <p class="text-xs sm:text-sm text-stone-500">
            Showing <span class="font-semibold text-stone-900">{{ $products->total() }}</span> plants
        </p>

        <div class="flex items-center gap-3">
            {{-- Mobile Filter Toggle --}}
            <button type="button"
                    @click="mobileFiltersOpen = true"
                    class="lg:hidden inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-stone-300 text-stone-700 text-xs font-semibold shadow-sm hover:bg-stone-50">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <span>Filters</span>
            </button>

            {{-- Sort By Selector --}}
            <div class="flex items-center gap-2">
                <label for="category-sort" class="text-xs font-medium text-stone-500 shrink-0">Sort:</label>
                <select id="category-sort"
                        onchange="const url = new URL(window.location.href); url.searchParams.set('sort', this.value); url.searchParams.delete('page'); window.location.href = url.toString();"
                        class="text-xs rounded-xl border border-stone-300 bg-white py-1.5 pl-2.5 pr-7 font-medium text-stone-800 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600">
                    <option value="featured" {{ ($filters['sort'] ?? 'featured') === 'featured' ? 'selected' : '' }}>Featured</option>
                    <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="name_asc" {{ ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Main Grid & Filter --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        {{-- Desktop Filter Sidebar --}}
        <aside class="hidden lg:block lg:col-span-1 bg-white p-5 rounded-2xl border border-stone-200/80 shadow-sm sticky top-24">
            <x-storefront.filter-sidebar
                :facets="$facets"
                :currentFilters="$filters"
                :activeCategory="$category"
                :actionUrl="route('categories.show', $category->slug)" />
        </aside>

        {{-- Mobile Filter Drawer Modal --}}
        <div x-show="mobileFiltersOpen"
             x-cloak
             @keydown.escape.window="mobileFiltersOpen = false"
             class="relative z-50 lg:hidden"
             role="dialog"
             aria-modal="true"
             aria-label="{{ $category->name }} filters">
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
                        <button type="button"
                                @click="mobileFiltersOpen = false"
                                aria-label="Close filters"
                                class="text-stone-400 hover:text-stone-600 p-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <x-storefront.filter-sidebar
                        :facets="$facets"
                        :currentFilters="$filters"
                        :activeCategory="$category"
                        :actionUrl="route('categories.show', $category->slug)" />
                </div>
            </div>
        </div>

        {{-- Products Grid --}}
        <section class="lg:col-span-3 space-y-8" aria-label="{{ $category->name }} Catalog">
            @if($products->isEmpty())
                <div class="p-12 text-center bg-white rounded-2xl border border-stone-200/80 shadow-sm space-y-4">
                    <p class="text-stone-500 text-sm">No botanical specimens found in this category matching your filters.</p>
                    <a href="{{ route('categories.show', $category->slug) }}"
                       class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-800 text-white text-xs font-semibold">
                        Reset Filters
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    @foreach($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>

                <div class="pt-6 border-t border-stone-200">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
