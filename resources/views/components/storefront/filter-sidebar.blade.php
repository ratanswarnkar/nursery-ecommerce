@props([
    'facets' => [],
    'currentFilters' => [],
    'activeCategory' => null,
    'activeBrand' => null,
    'actionUrl' => route('shop.index'),
])

@php
    $selectedAttributes = $currentFilters['attributes'] ?? [];
    $selectedBrands = (array) ($currentFilters['brand'] ?? []);
    $selectedMinPrice = $currentFilters['min_price'] ?? '';
    $selectedMaxPrice = $currentFilters['max_price'] ?? '';
    $inStockOnly = !empty($currentFilters['in_stock']);
    $categories = collect($facets['categories'] ?? []);
    $brands = collect($facets['brands'] ?? []);
    $attributes = collect($facets['attributes'] ?? []);
    $currentSort = $currentFilters['sort'] ?? 'featured';
    $searchQuery = $currentFilters['q'] ?? '';
    $currentCategory = $currentFilters['category'] ?? '';
@endphp

<form method="GET" action="{{ $actionUrl }}" id="catalog-filter-form" class="space-y-6">
    {{-- Preserve Category, Sort and Search --}}
    @if($currentCategory && !$activeCategory)
        <input type="hidden" name="category" value="{{ $currentCategory }}">
    @endif
    @if($searchQuery)
        <input type="hidden" name="q" value="{{ $searchQuery }}">
    @endif
    @if($currentSort && $currentSort !== 'featured')
        <input type="hidden" name="sort" value="{{ $currentSort }}">
    @endif

    {{-- Filter Header / Reset --}}
    <div class="flex items-center justify-between pb-4 border-b border-stone-200">
        <h2 class="text-sm font-bold uppercase tracking-wider text-stone-900 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            Refine Catalog
        </h2>
        <a href="{{ $actionUrl }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 hover:underline">
            Reset All
        </a>
    </div>

    {{-- Stock Status Filter --}}
    <div class="space-y-3 pb-5 border-b border-stone-100">
        <h3 class="text-xs font-bold uppercase tracking-wider text-stone-700">Availability</h3>
        <label class="flex items-center gap-2.5 cursor-pointer text-sm text-stone-700 hover:text-emerald-800 select-none">
            <input type="checkbox"
                   name="in_stock"
                   value="1"
                   {{ $inStockOnly ? 'checked' : '' }}
                   onchange="this.form.submit()"
                   class="w-4 h-4 rounded border-stone-300 text-emerald-700 focus:ring-emerald-500">
            <span class="font-medium">In Stock Only</span>
        </label>
    </div>

    {{-- Price Filter --}}
    <div class="space-y-3 pb-5 border-b border-stone-100" x-data="{ minPrice: '{{ $selectedMinPrice }}', maxPrice: '{{ $selectedMaxPrice }}' }">
        <h3 class="text-xs font-bold uppercase tracking-wider text-stone-700">Price Range (₹)</h3>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="min_price" class="sr-only">Min Price</label>
                <input type="number"
                       name="min_price"
                       id="min_price"
                       min="0"
                       step="1"
                       placeholder="Min ₹"
                       x-model="minPrice"
                       value="{{ $selectedMinPrice }}"
                       class="w-full px-3 py-1.5 text-xs rounded-lg border border-stone-300 focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
            </div>
            <div>
                <label for="max_price" class="sr-only">Max Price</label>
                <input type="number"
                       name="max_price"
                       id="max_price"
                       min="0"
                       step="1"
                       placeholder="Max ₹"
                       x-model="maxPrice"
                       value="{{ $selectedMaxPrice }}"
                       class="w-full px-3 py-1.5 text-xs rounded-lg border border-stone-300 focus:ring-1 focus:ring-emerald-600 focus:border-emerald-600">
            </div>
        </div>
        <button type="submit"
                class="w-full py-1.5 px-3 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-800 text-xs font-semibold transition-colors">
            Apply Price
        </button>
    </div>

    {{-- Categories Filter (if not already locked to a specific category) --}}
    @if(!$activeCategory && $categories->isNotEmpty())
        <div class="space-y-3 pb-5 border-b border-stone-100" x-data="{ open: true }">
            <button type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-stone-700">
                <span>Plant Categories</span>
                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="space-y-1 max-h-48 overflow-y-auto pr-1">
                @foreach($categories as $cat)
                    @php
                        $catSlug = data_get($cat, 'slug');
                        $catName = data_get($cat, 'name');
                        $catCount = data_get($cat, 'count', data_get($cat, 'active_products_count', 0));
                    @endphp
                    @if($catSlug)
                        <a href="{{ route('categories.show', $catSlug) }}"
                           class="flex items-center justify-between text-xs py-1 px-1.5 rounded hover:bg-emerald-50 text-stone-600 hover:text-emerald-900 transition-colors">
                            <span class="truncate">{{ $catName }}</span>
                            <span class="text-[10px] text-stone-400 font-mono">({{ $catCount }})</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Brands Filter (if not already locked to a specific brand) --}}
    @if(!$activeBrand && $brands->isNotEmpty())
        <div class="space-y-3 pb-5 border-b border-stone-100" x-data="{ open: true }">
            <button type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-stone-700">
                <span>Brands / Growers</span>
                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                @foreach($brands as $brand)
                    @php
                        $brandSlug = data_get($brand, 'slug');
                        $brandName = data_get($brand, 'name');
                        $brandCount = data_get($brand, 'count', data_get($brand, 'active_products_count', 0));
                    @endphp
                    @if($brandSlug)
                        <label class="flex items-center justify-between gap-2 text-xs text-stone-600 hover:text-stone-900 cursor-pointer select-none">
                            <div class="flex items-center gap-2 truncate">
                                <input type="checkbox"
                                       name="brand[]"
                                       value="{{ $brandSlug }}"
                                       {{ in_array($brandSlug, $selectedBrands, true) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="w-3.5 h-3.5 rounded border-stone-300 text-emerald-700 focus:ring-emerald-500">
                                <span class="truncate">{{ $brandName }}</span>
                            </div>
                            @if($brandCount !== null)
                                <span class="text-[10px] text-stone-400 font-mono">({{ $brandCount }})</span>
                            @endif
                        </label>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filterable Attributes (e.g. Sunlight, Soil, Pot Size) --}}
    @foreach($attributes as $attribute)
        @php
            $attrId = data_get($attribute, 'id');
            $attrName = data_get($attribute, 'name');
            $attrValues = collect(data_get($attribute, 'values', []));
            $attrValuesSelected = $selectedAttributes[$attrId] ?? [];
            if (!is_array($attrValuesSelected)) {
                $attrValuesSelected = [$attrValuesSelected];
            }
        @endphp
        @if($attrValues->isNotEmpty())
            <div class="space-y-2.5 pb-5 border-b border-stone-100" x-data="{ open: true }">
                <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-stone-700">
                    <span>{{ $attrName }}</span>
                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                    @foreach($attrValues as $val)
                        @php
                            $valId = data_get($val, 'id');
                            $valText = data_get($val, 'label') ?: data_get($val, 'value');
                            $valCount = data_get($val, 'count');
                        @endphp
                        <label class="flex items-center justify-between gap-2 text-xs text-stone-600 hover:text-stone-900 cursor-pointer select-none">
                            <div class="flex items-center gap-2 truncate">
                                <input type="checkbox"
                                       name="attributes[{{ $attrId }}][]"
                                       value="{{ $valId }}"
                                       {{ in_array((string)$valId, array_map('strval', $attrValuesSelected), true) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="w-3.5 h-3.5 rounded border-stone-300 text-emerald-700 focus:ring-emerald-500">
                                <span class="truncate">{{ $valText }}</span>
                            </div>
                            @if($valCount !== null)
                                <span class="text-[10px] text-stone-400 font-mono">({{ $valCount }})</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    <button type="submit"
            class="w-full py-2.5 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-semibold text-xs tracking-wide shadow-sm transition-colors">
        Filter Products
    </button>
</form>
