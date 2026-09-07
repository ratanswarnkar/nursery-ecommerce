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
@endphp

<div class="space-y-12"
     x-data="{
        variants: @js($variantMatrix),
        dimensions: @js($optionDimensions),
        selectedVariantId: {{ $defaultVariantId ?? ($defaultVariant ? $defaultVariant->id : 'null') }},
        selectedDimensions: {},
        quantity: 1,
        activeImageUrl: '{{ $primaryImage ? $primaryImage->url : '' }}',

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

    {{-- Main Product Detail Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
        {{-- Gallery (Cols 1-7) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="relative aspect-square w-full bg-stone-100 rounded-3xl overflow-hidden border border-stone-200/80 shadow-sm flex items-center justify-center">
                <template x-if="activeImageUrl">
                    <img :src="activeImageUrl"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover object-center transition-all duration-300">
                </template>
                <template x-if="!activeImageUrl">
                    @if($primaryImage)
                        <img src="{{ $primaryImage->url }}"
                             alt="{{ $primaryImage->alt_text ?? $product->name }}"
                             class="w-full h-full object-cover object-center">
                    @else
                        <div class="flex flex-col items-center justify-center text-stone-400">
                            <svg class="w-20 h-20 stroke-1 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                            </svg>
                            <span class="text-xs">Botanical Specimen</span>
                        </div>
                    @endif
                </template>
            </div>

            {{-- Thumbnails --}}
            @if($product->images->count() > 1)
                <div class="flex items-center gap-3 overflow-x-auto pb-2">
                    @foreach($product->images as $img)
                        <button type="button"
                                @click="activeImageUrl = '{{ $img->url }}'"
                                :class="activeImageUrl === '{{ $img->url }}' ? 'ring-2 ring-emerald-700 border-transparent' : 'border-stone-200 opacity-70 hover:opacity-100'"
                                class="w-20 h-20 rounded-xl overflow-hidden border bg-stone-100 shrink-0 transition-all">
                            <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? $product->name }}" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Product Information & Purchase Form (Cols 8-12) --}}
        <div class="lg:col-span-5 space-y-6">
            {{-- Brand / Category / Status --}}
            <div class="space-y-1">
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
                        <template x-if="currentVariant">
                            <span x-text="currentVariant.stock > 5 ? 'In Stock' : (currentVariant.stock > 0 ? ('Only ' + currentVariant.stock + ' Left') : 'Sold Out')"
                                  :class="currentVariant.stock > 5 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (currentVariant.stock > 0 ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-stone-100 text-stone-500 border-stone-200')"
                                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border">
                            </span>
                        </template>
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
                    <span class="text-xl font-bold text-stone-500">Unavailable</span>
                </template>
            </div>

            {{-- Short Description --}}
            @if($product->short_description)
                <p class="text-sm text-stone-600 leading-relaxed">
                    {{ $product->short_description }}
                </p>
            @endif

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
                                    :disabled="quantity <= 1"
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
                                   class="w-full text-center border-0 text-sm font-semibold text-stone-800 focus:ring-0 p-0">
                            <button type="button"
                                    @click="if (quantity < maxQuantity) quantity++"
                                    :disabled="quantity >= maxQuantity"
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
                            <span x-text="(!currentVariant || currentVariant.stock <= 0) ? 'Out of Stock' : 'Add to Cart'">Add to Cart</span>
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
                    <span x-text="currentVariant ? currentVariant.sku : '{{ $product->sku }}'" class="font-mono">{{ $defaultVariant->sku ?? $product->sku }}</span>
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

    {{-- Product Long Description & Botanical Care Tabs --}}
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-stone-200/80 shadow-sm space-y-6">
        <h2 class="text-xl font-bold text-stone-900 border-b border-stone-200 pb-3">Botanical Details & Care Guide</h2>
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
