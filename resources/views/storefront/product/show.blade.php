@extends('layouts.storefront')

@section('seo')
    <title>{{ $seoData['title'] ?? ($product->name . ' | Sugandha Farms and Nursery') }}</title>
    <meta name="description" content="{{ $seoData['description'] ?? ($product->short_description ?: 'Buy ' . $product->name . ' online.') }}">
    <link rel="canonical" href="{{ $seoData['canonical'] ?? route('products.show', $product->slug) }}">
    <meta property="og:title" content="{{ $seoData['title'] ?? $product->name }}">
    <meta property="og:description" content="{{ $seoData['description'] ?? $product->short_description }}">
    <meta property="og:url" content="{{ $seoData['canonical'] ?? route('products.show', $product->slug) }}">
    <meta property="og:type" content="product">
    @if(!empty($seoData['schema']))
        {!! $seoData['schema'] !!}
    @endif
@endsection

@section('content')
@php
    $primaryImage = $product->primaryImage ?? $product->images->first();
    $defaultVariant = $activeVariants->firstWhere('id', $defaultVariantId) ?? $activeVariants->first();
    $categories = $product->categories;
    $brand = $product->brand;
    
    $breadcrumbs = [
        ['name' => 'Shop', 'url' => route('shop.index')],
    ];
    if ($categories->isNotEmpty()) {
        $breadcrumbs[] = ['name' => $categories->first()->name, 'url' => route('categories.show', $categories->first()->slug)];
    }
    $breadcrumbs[] = ['name' => $product->name, 'url' => ''];

    $defaultStockStatus = 'unavailable';
    $defaultStockLabel = 'Currently Unavailable';
    if ($defaultVariant) {
        $defaultSafetyStock = (int) $defaultVariant->inventories
            ->filter(fn ($inv) => ! $inv->warehouse || $inv->warehouse->is_active)
            ->sum('safety_stock');
        $defaultAvailable = (int) $defaultVariant->available_stock;
        if ($defaultAvailable <= 0) {
            $defaultStockStatus = 'out_of_stock';
            $defaultStockLabel = 'Out of Stock';
        } elseif ($defaultSafetyStock > 0 && $defaultAvailable <= $defaultSafetyStock) {
            $defaultStockStatus = 'low_stock';
            $defaultStockLabel = 'Low Stock: Only ' . $defaultAvailable . ' Left';
        } else {
            $defaultStockStatus = 'in_stock';
            $defaultStockLabel = 'In Stock';
        }
    }
@endphp

<div class="space-y-10"
     x-data="{
        variants: @js($variantMatrix),
        dimensions: @js($optionDimensions),
        selectedVariantId: {{ $defaultVariantId ?? ($defaultVariant ? $defaultVariant->id : 'null') }},
        selectedDimensions: {},
        quantity: 1,
        activeImageUrl: '{{ $primaryImage ? $primaryImage->url : '' }}',
        isZoomed: false,

        init() {
            if (this.selectedVariantId) {
                const current = this.variants.find(v => v.id === this.selectedVariantId);
                if (current) {
                    this.selectedDimensions = Object.assign({}, current.attribute_values);
                    if (current.image_url) {
                        this.activeImageUrl = current.image_url;
                    }
                }
            } else if (this.variants.length > 0) {
                this.selectedVariantId = this.variants[0].id;
                this.selectedDimensions = Object.assign({}, this.variants[0].attribute_values);
                if (this.variants[0].image_url) {
                    this.activeImageUrl = this.variants[0].image_url;
                }
            }
        },

        get currentVariant() {
            return this.variants.find(v => v.id === this.selectedVariantId) || null;
        },

        get maxQuantity() {
            if (!this.currentVariant) return 1;
            return Math.min(50, Math.max(0, this.currentVariant.stock));
        },

        selectDimensionValue(attrId, valId) {
            this.selectedDimensions[attrId] = valId;
            // Attempt exact match
            const match = this.variants.find(v => {
                return Object.entries(this.selectedDimensions).every(([k, val]) => v.attribute_values[k] == val);
            });
            if (match) {
                this.selectedVariantId = match.id;
                if (match.image_url) this.activeImageUrl = match.image_url;
            } else {
                // Fallback to first variant matching this newly selected value
                const fallback = this.variants.find(v => v.attribute_values[attrId] == valId);
                if (fallback) {
                    this.selectedVariantId = fallback.id;
                    this.selectedDimensions = Object.assign({}, fallback.attribute_values);
                    if (fallback.image_url) this.activeImageUrl = fallback.image_url;
                }
            }
            if (this.quantity > this.maxQuantity && this.maxQuantity > 0) {
                this.quantity = this.maxQuantity;
            }
        },

        isOptionAvailable(attrId, valId) {
            return this.variants.some(v => v.attribute_values[attrId] == valId);
        }
     }">

    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="$breadcrumbs" />

    {{-- Cart Addition Session Feedback --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between gap-4 shadow-sm" role="alert">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('cart.index') }}" class="px-3 py-1.5 rounded-lg bg-emerald-800 text-white text-xs font-semibold hover:bg-emerald-900 transition-colors">
                    View Cart
                </a>
            </div>
        </div>
    @endif

    {{-- Active Product with Inactive Variants Notice --}}
    @if($activeVariants->isEmpty())
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold">Currently Unavailable</p>
                <p class="text-xs text-amber-700 mt-0.5">This plant specimen is currently out of seasonal rotation or undergoing greenhouse cultivation. Please check back soon or explore our other botanical selections.</p>
            </div>
        </div>
    @endif

    {{-- Main Product Detail Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
        {{-- Gallery (Cols 1-7) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="relative aspect-square w-full bg-stone-100 rounded-3xl overflow-hidden border border-stone-200/80 shadow-sm flex items-center justify-center group">
                <template x-if="activeImageUrl">
                    <div class="w-full h-full cursor-zoom-in" @click="isZoomed = true">
                        <img :src="activeImageUrl"
                             alt="{{ $product->name }}"
                             loading="eager"
                             class="w-full h-full object-cover object-center transition-all duration-300 group-hover:scale-105">
                    </div>
                </template>
                <template x-if="!activeImageUrl">
                    @if($primaryImage)
                        <div class="w-full h-full cursor-zoom-in" @click="activeImageUrl = '{{ $primaryImage->url }}'; isZoomed = true">
                            <img src="{{ $primaryImage->url }}"
                                 alt="{{ $primaryImage->alt_text ?? $product->name }}"
                                 loading="eager"
                                 class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center text-stone-400">
                            <svg class="w-20 h-20 stroke-1 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                            </svg>
                            <span class="text-xs">Botanical Specimen</span>
                        </div>
                    @endif
                </template>

                {{-- Zoom Overlay Button --}}
                <template x-if="activeImageUrl">
                    <button type="button"
                            @click="isZoomed = true"
                            aria-label="Enlarge product image"
                            class="absolute bottom-3 right-3 p-2 rounded-xl bg-white/80 hover:bg-white text-stone-700 shadow-md backdrop-blur-xs border border-stone-200 transition-all opacity-90 group-hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                        </svg>
                    </button>
                </template>
            </div>

            {{-- Thumbnails --}}
            @if($product->images->count() > 1)
                <div class="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-none" role="tablist" aria-label="Product thumbnails">
                    @foreach($product->images as $index => $img)
                        <button type="button"
                                role="tab"
                                aria-label="View product image {{ $index + 1 }} of {{ $product->images->count() }}"
                                :aria-selected="activeImageUrl === '{{ $img->url }}' ? 'true' : 'false'"
                                @click="activeImageUrl = '{{ $img->url }}'"
                                @keydown.enter.prevent="activeImageUrl = '{{ $img->url }}'"
                                @keydown.space.prevent="activeImageUrl = '{{ $img->url }}'"
                                :class="activeImageUrl === '{{ $img->url }}' ? 'ring-2 ring-emerald-700 border-transparent' : 'border-stone-200 opacity-70 hover:opacity-100'"
                                class="w-20 h-20 rounded-xl overflow-hidden border bg-stone-100 shrink-0 transition-all focus:outline-hidden focus:ring-2 focus:ring-emerald-600">
                            <img src="{{ $img->url }}"
                                 alt="{{ $img->alt_text ?? ($product->name . ' thumbnail ' . ($index + 1)) }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Image Lightbox Zoom Modal --}}
            <div x-show="isZoomed"
                 x-cloak
                 @keydown.escape.window="isZoomed = false"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/90 backdrop-blur-sm"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Enlarged product image view">
                <button type="button"
                        @click="isZoomed = false"
                        aria-label="Close image viewer"
                        class="absolute top-4 right-4 text-white/80 hover:text-white p-2.5 rounded-full bg-stone-900/70 hover:bg-stone-900 transition-colors focus:outline-hidden focus:ring-2 focus:ring-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="max-w-4xl max-h-[85vh] overflow-hidden rounded-2xl border border-white/10 shadow-2xl flex items-center justify-center"
                     @click.away="isZoomed = false">
                    <img :src="activeImageUrl"
                         alt="{{ $product->name }}"
                         class="w-full h-full max-h-[85vh] object-contain">
                </div>
            </div>
        </div>

        {{-- Product Information & Purchase Form (Cols 8-12) --}}
        <div class="lg:col-span-5 space-y-6">
            {{-- Brand / Category / Status --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    @if($brand)
                        <a href="{{ route('brands.show', $brand->slug) }}" class="text-xs font-bold uppercase tracking-wider text-stone-500 hover:text-emerald-800 transition-colors">
                            {{ $brand->name }}
                        </a>
                    @elseif($categories->isNotEmpty())
                        <a href="{{ route('categories.show', $categories->first()->slug) }}" class="text-xs font-bold uppercase tracking-wider text-emerald-800">
                            {{ $categories->first()->name }}
                        </a>
                    @else
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Living Botanical</span>
                    @endif

                    <div class="flex items-center gap-2">
                        <span x-text="currentVariant ? (currentVariant.stock_status === 'out_of_stock' ? 'Out of Stock' : (currentVariant.stock_status === 'low_stock' ? ('Low Stock: Only ' + currentVariant.stock + ' Left') : 'In Stock')) : 'Currently Unavailable'"
                              :class="{
                                  'bg-emerald-50 text-emerald-800 border-emerald-200': currentVariant && currentVariant.stock_status === 'in_stock',
                                  'bg-amber-50 text-amber-800 border-amber-200': currentVariant && currentVariant.stock_status === 'low_stock',
                                  'bg-stone-100 text-stone-500 border-stone-200': !currentVariant || currentVariant.stock_status === 'out_of_stock'
                              }"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $defaultStockStatus === 'in_stock' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : ($defaultStockStatus === 'low_stock' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-stone-100 text-stone-500 border-stone-200') }}">
                            {{ $defaultStockLabel }}
                        </span>
                    </div>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight leading-tight">
                    {{ $product->name }}
                </h1>
            </div>

            {{-- Price Display --}}
            <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200/80 flex items-baseline gap-3">
                <template x-if="currentVariant">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <span class="text-3xl font-extrabold text-emerald-950 tracking-tight"
                              x-text="'₹' + Number(currentVariant.price).toFixed(2)">
                            ₹{{ number_format((float)($defaultVariant->price ?? 0), 2) }}
                        </span>

                        <template x-if="currentVariant.compare_at_price && Number(currentVariant.compare_at_price) > Number(currentVariant.price)">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-stone-400 line-through"
                                      x-text="'₹' + Number(currentVariant.compare_at_price).toFixed(2)">
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-xs font-bold bg-terracotta-100 text-terracotta-800"
                                      x-text="currentVariant.discount_percent + '% OFF'">
                                </span>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!currentVariant">
                    <span class="text-xl font-bold text-stone-500">Currently Unavailable</span>
                </template>
            </div>

            {{-- Short Description --}}
            @if($product->short_description)
                <p class="text-sm text-stone-600 leading-relaxed">
                    {{ $product->short_description }}
                </p>
            @endif

            {{-- Delivery Information Section (Scope C) --}}
            <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 space-y-2.5">
                <div class="text-xs font-bold uppercase tracking-wider text-emerald-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span>Direct Nursery Delivery</span>
                </div>
                <ul class="text-xs text-emerald-950/90 space-y-1.5 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-700 font-bold shrink-0">•</span>
                        <span>Delivery available <strong>ONLY within Delhi NCR</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-700 font-bold shrink-0">•</span>
                        <span>Dispatched directly from local greenhouse and <strong>delivered within 3 days</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-700 font-bold shrink-0">•</span>
                        <span>Orders <strong>ABOVE ₹1,000</strong> qualify for <strong>FREE delivery</strong> (standard ₹99 applies on orders of ₹1,000 or below at checkout).</span>
                    </li>
                </ul>
            </div>

            {{-- Variant Selection Options --}}
            <template x-for="dim in dimensions" :key="dim.id">
                <div class="space-y-2 pt-2 border-t border-stone-200">
                    <label class="text-xs font-bold uppercase tracking-wider text-stone-700 flex items-center justify-between">
                        <span x-text="dim.name"></span>
                    </label>

                    <div class="flex items-center flex-wrap gap-2">
                        <template x-for="opt in dim.values" :key="opt.id">
                            <button type="button"
                                    @click="selectDimensionValue(dim.id, opt.id)"
                                    :disabled="!isOptionAvailable(dim.id, opt.id)"
                                    :class="{
                                        'bg-emerald-800 text-white border-emerald-800 shadow-sm': selectedDimensions[dim.id] == opt.id,
                                        'bg-white text-stone-700 border-stone-300 hover:border-stone-400': selectedDimensions[dim.id] != opt.id && isOptionAvailable(dim.id, opt.id),
                                        'bg-stone-100 text-stone-300 border-stone-200 line-through cursor-not-allowed': !isOptionAvailable(dim.id, opt.id)
                                    }"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all">
                                <span x-text="opt.label || opt.value"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Add To Cart Form (Server-authoritative POST) --}}
            <form method="POST" action="{{ route('cart.items.store') }}" class="space-y-4 pt-4 border-t border-stone-200">
                @csrf
                <input type="hidden" name="product_variant_id" :value="selectedVariantId" value="{{ $defaultVariantId ?? ($defaultVariant ? $defaultVariant->id : '') }}">

                <div class="flex items-center gap-4">
                    {{-- Quantity Selector --}}
                    <div class="w-32">
                        <label for="pdp-quantity" class="sr-only">Quantity</label>
                        <div class="flex items-center border border-stone-300 rounded-xl bg-white overflow-hidden">
                            <button type="button"
                                    @click="if (quantity > 1) quantity--"
                                    :disabled="quantity <= 1 || !currentVariant || currentVariant.stock <= 0"
                                    class="px-3 py-2.5 text-stone-600 hover:bg-stone-100 transition-colors disabled:opacity-40">
                                -
                            </button>
                            <input type="number"
                                   name="quantity"
                                   id="pdp-quantity"
                                   min="1"
                                   :max="maxQuantity"
                                   x-model.number="quantity"
                                   value="1"
                                   :disabled="!currentVariant || currentVariant.stock <= 0"
                                   class="w-full text-center border-0 text-sm font-semibold text-stone-800 focus:ring-0 p-0 disabled:bg-stone-50 disabled:text-stone-400">
                            <button type="button"
                                    @click="if (quantity < maxQuantity) quantity++"
                                    :disabled="quantity >= maxQuantity || !currentVariant || currentVariant.stock <= 0"
                                    class="px-3 py-2.5 text-stone-600 hover:bg-stone-100 transition-colors disabled:opacity-40">
                                +
                            </button>
                        </div>
                    </div>

                    {{-- Add to Cart Button --}}
                    <div class="flex-grow">
                        <button type="submit"
                                :disabled="!currentVariant || currentVariant.stock <= 0"
                                :class="(!currentVariant || currentVariant.stock <= 0) ? 'bg-stone-300 cursor-not-allowed text-stone-500' : 'bg-emerald-800 hover:bg-emerald-900 text-white shadow-lg shadow-emerald-900/10'"
                                class="w-full py-3 px-6 rounded-xl font-bold text-sm tracking-wide transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span x-text="(!currentVariant ? 'Currently Unavailable' : (currentVariant.stock <= 0 ? 'Out of Stock' : 'Add to Cart'))">
                                {{ $defaultVariant && $defaultVariant->available_stock > 0 ? 'Add to Cart' : 'Currently Unavailable' }}
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Stock Disclaimer Notice --}}
                <p class="text-[11px] text-stone-500 italic">
                    * Items in cart are not reserved. Real-time nursery stock is confirmed again during checkout.
                </p>
            </form>

            {{-- Metadata Row (SKU, Category, Botanical Specs) --}}
            <div class="pt-4 border-t border-stone-200 text-xs text-stone-500 space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-stone-700">SKU:</span>
                    <span x-text="currentVariant ? currentVariant.sku : '{{ $product->base_sku }}'" class="font-mono">{{ $defaultVariant->sku ?? $product->base_sku }}</span>
                </div>
                @if($categories->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-stone-700">Categories:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @foreach($categories as $cat)
                                <a href="{{ route('categories.show', $cat->slug) }}" class="text-emerald-700 hover:underline">
                                    {{ $cat->name }}
                                </a>{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Plant Specifications & Care Guide (Scope A) --}}
    @if(!empty($product->custom_attributes) && is_array($product->custom_attributes))
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200/80 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-stone-900 border-b border-stone-200 pb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Botanical Specifications &amp; Care Details
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($product->custom_attributes as $key => $val)
                    @if(!empty($val) && !is_array($val))
                        <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200/60">
                            <dt class="text-xs font-bold text-stone-500 uppercase tracking-wider">
                                {{ \Illuminate\Support\Str::headline($key) }}
                            </dt>
                            <dd class="text-sm font-semibold text-stone-800 mt-1 capitalize">
                                {{ str_replace('_', ' ', (string) $val) }}
                            </dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
    @endif

    {{-- Product Long Description & Botanical Care Tabs --}}
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-stone-200/80 shadow-sm space-y-6">
        <h2 class="text-xl font-bold text-stone-900 border-b border-stone-200 pb-3">Botanical Details &amp; Overview</h2>
        <div class="prose prose-stone max-w-none text-stone-700 text-sm sm:text-base leading-relaxed">
            @if($product->description)
                <div class="whitespace-pre-line">{{ $product->description }}</div>
            @else
                <p>Acclimatized specimen nurtured in professional nursery greenhouse conditions. Suitable for indoor or shaded patio placement with moderate natural ambient light.</p>
            @endif
        </div>
    </div>

    {{-- Related Products --}}
    @if($relatedProducts->isNotEmpty())
        <section class="space-y-6 pt-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">You May Also Like</span>
                    <h2 class="text-2xl font-bold text-stone-900 mt-1">Complementary Botanical Specimens</h2>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($relatedProducts as $related)
                    <x-storefront.product-card :product="$related" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
