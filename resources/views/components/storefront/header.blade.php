@php
    $cartCount = 0;
    try {
        $customer = auth('customer')->user();
        $sessionCartId = session('cart_id');
        if ($customer) {
            $activeCart = \App\Models\Cart::where('customer_id', $customer->id)->where('is_active', true)->first();
        } elseif ($sessionCartId) {
            $activeCart = \App\Models\Cart::where('id', $sessionCartId)->where('is_active', true)->first();
        } else {
            $activeCart = \App\Models\Cart::where('session_id', session()->getId())->where('is_active', true)->first();
        }
        if ($activeCart) {
            $cartCount = (int) $activeCart->items()->sum('quantity');
        }
    } catch (\Throwable $e) {
        $cartCount = 0;
    }

    $agreedCategorySlugs = [
        'indoor-plants',
        'flowering-plants',
        'air-purifying',
        'fruit-plants',
        'outdoor-plants',
        'herbal-medicinal-plants',
        'flowering-saplings',
        'terracotta-pots',
        'plant-care',
    ];

    $navCategories = \App\Models\Category::query()
        ->where('is_active', true)
        ->whereIn('slug', $agreedCategorySlugs)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    if ($navCategories->isEmpty()) {
        $navCategories = \App\Models\Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(9)
            ->get();
    }
@endphp

<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20 gap-4">

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center lg:hidden">
                <button type="button" 
                        @click="mobileMenuOpen = true"
                        class="p-2 rounded-lg text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        aria-label="Open mobile menu">
                    <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Botanical Branding Logo -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="storefront-logo max-w-[140px] sm:max-w-[180px] flex items-center gap-2 sm:gap-2.5 group">
                    <div class="storefront-logo-icon w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-green-700 flex items-center justify-center text-white shadow-md shadow-emerald-700/20 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs sm:text-base font-extrabold tracking-tight text-slate-900 group-hover:text-emerald-700 transition-colors leading-tight whitespace-nowrap">Sugandha <span class="text-emerald-600">Farms</span></span>
                        <span class="block text-[8px] sm:text-[10px] font-bold uppercase tracking-widest text-emerald-700/80 -mt-0.5 whitespace-nowrap">&amp; Nursery</span>
                    </div>
                </a>
            </div>

            <!-- Desktop Search Bar -->
            <div class="hidden md:flex flex-1 max-w-lg mx-4">
                <form method="GET" action="{{ route('shop.index') }}" class="w-full relative">
                    <label for="header-search-desktop" class="sr-only">Search indoor plants, saplings, planters, soil</label>
                    <input type="text" 
                           id="header-search-desktop"
                           name="q" 
                           value="{{ request('q') }}"
                           placeholder="Search indoor plants, saplings, planters, soil..." 
                           maxlength="100"
                           class="w-full pl-11 pr-4 py-2.5 bg-slate-100/90 border border-slate-200 rounded-full text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button type="submit" class="sr-only">Search</button>
                </form>
            </div>

            <!-- User Actions & Cart Header -->
            <div class="flex items-center gap-3">
                <!-- Shop Catalog Link -->
                <a href="{{ route('shop.index') }}" class="hidden sm:inline-flex items-center px-3.5 py-1.5 rounded-lg text-sm font-semibold text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 transition">
                    Explore Shop
                </a>

                <!-- Customer Account Dropdown / Link -->
                @if(auth('customer')->check())
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button type="button" 
                                @click="userMenuOpen = !userMenuOpen" 
                                @click.away="userMenuOpen = false"
                                aria-haspopup="true"
                                :aria-expanded="userMenuOpen.toString()"
                                aria-label="Customer account menu"
                                class="flex items-center gap-2 p-2 rounded-lg text-slate-700 hover:text-emerald-700 hover:bg-slate-100 transition focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth('customer')->user()->name ?: 'C', 0, 1)) }}
                            </div>
                            <span class="hidden lg:inline text-sm font-semibold">{{ auth('customer')->user()->name ?: 'Account' }}</span>
                            <svg class="w-4 h-4 text-slate-400" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="userMenuOpen" 
                             x-cloak 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-xl ring-1 ring-black/5 py-1.5 z-50 text-sm">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs text-slate-500 font-medium">Signed in as</p>
                                <p class="text-sm font-bold text-slate-900 truncate">{{ auth('customer')->user()->phone }}</p>
                            </div>
                            <a href="{{ route('customer.home') }}" class="block px-4 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 font-medium">Dashboard</a>
                            <a href="{{ route('account.orders.index') }}" class="block px-4 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 font-medium">My Orders</a>
                            <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 font-medium">My Profile</a>
                            <a href="{{ route('account.addresses.index') }}" class="block px-4 py-2 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 font-medium">Saved Addresses</a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 font-medium">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('customer.login') }}" class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">
                        <svg class="w-5 h-5 text-slate-500" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="hidden sm:inline">Sign In</span>
                    </a>
                @endif

                <!-- Shopping Cart Icon with Live Count Badge -->
                <a href="{{ route('cart.index') }}" 
                   class="relative inline-flex items-center justify-center p-2.5 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-xl transition group focus:outline-none focus:ring-2 focus:ring-emerald-500"
                   aria-label="Shopping Cart with {{ $cartCount }} items"
                   title="View Shopping Cart">
                    <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-[11px] font-bold leading-none text-white bg-emerald-600 rounded-full border-2 border-white shadow-sm min-w-[20px] h-5">
                            {{ $cartCount > 99 ? '99+' : $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>

        <!-- Desktop Category Sub-bar Navigation -->
        <nav class="hidden lg:flex items-center justify-between py-2.5 border-t border-slate-100 text-[13px] font-semibold text-slate-600">
            <div class="flex items-center gap-6 overflow-x-auto scrollbar-none">
                <a href="{{ route('shop.index') }}" class="hover:text-emerald-700 transition flex items-center gap-1 shrink-0 font-bold text-slate-800">
                    <span>All Plants</span>
                </a>
                @foreach($navCategories as $navCat)
                    <a href="{{ route('categories.show', $navCat->slug) }}" class="hover:text-emerald-700 transition whitespace-nowrap">
                        {{ $navCat->name }}
                    </a>
                @endforeach
            </div>
            <div class="flex items-center gap-4 pl-4 shrink-0 border-l border-slate-200">
                <a href="{{ route('services.landscaping') }}" class="text-emerald-700 hover:text-emerald-900 transition font-bold flex items-center gap-1.5 whitespace-nowrap">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582"/>
                    </svg>
                    <span>Landscaping Services</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Mobile Drawer Menu & Backdrop -->
    <div x-show="mobileMenuOpen" 
         x-cloak 
         @keydown.escape.window="mobileMenuOpen = false"
         class="relative z-50 lg:hidden" 
         role="dialog" 
         aria-modal="true"
         aria-label="Mobile navigation menu">
        <!-- Backdrop -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

        <div class="fixed inset-0 flex">
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4">
                
                <div class="flex items-center justify-between px-4 pb-4 border-b border-slate-100">
                    <span class="text-lg font-bold text-slate-900">Nursery Navigation</span>
                    <button type="button" 
                            @click="mobileMenuOpen = false"
                            aria-label="Close mobile navigation menu"
                            class="p-2 rounded-lg text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Search Form -->
                <div class="px-4 py-3">
                    <form method="GET" action="{{ route('shop.index') }}">
                        <label for="header-search-mobile" class="sr-only">Search plants</label>
                        <input type="text" 
                               id="header-search-mobile"
                               name="q" 
                               value="{{ request('q') }}"
                               placeholder="Search plants..." 
                               class="w-full px-3.5 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-emerald-500">
                    </form>
                </div>

                <!-- Mobile Category Navigation List -->
                <div class="flex-1 overflow-y-auto px-4 py-2 space-y-1 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">Home</a>
                    <a href="{{ route('shop.index') }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700 font-bold text-emerald-800">Shop All Plants</a>
                    
                    <div class="pt-2 pb-1 text-xs font-bold uppercase tracking-wider text-slate-400 px-3">Product Categories</div>
                    @foreach($navCategories as $navCat)
                        <a href="{{ route('categories.show', $navCat->slug) }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">
                            {{ $navCat->name }}
                        </a>
                    @endforeach

                    <div class="pt-3 pb-1 text-xs font-bold uppercase tracking-wider text-slate-400 px-3">Services</div>
                    <a href="{{ route('services.landscaping') }}" class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-50/80 text-emerald-800 font-semibold hover:bg-emerald-100 transition">
                        <span>Landscaping Services</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-emerald-700 text-white px-1.5 py-0.5 rounded">Service</span>
                    </a>

                    <div class="pt-4 pb-1 text-xs font-bold uppercase tracking-wider text-slate-400 px-3">Account & Cart</div>
                    <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">
                        <span>Shopping Cart</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">{{ $cartCount }}</span>
                    </a>
                    @if(auth('customer')->check())
                        <a href="{{ route('customer.home') }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">Customer Dashboard</a>
                        <a href="{{ route('account.orders.index') }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">My Orders</a>
                        <a href="{{ route('account.profile') }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">My Profile</a>
                        <a href="{{ route('account.addresses.index') }}" class="block px-3 py-2 rounded-lg hover:bg-emerald-50 hover:text-emerald-700">Saved Addresses</a>
                        <form method="POST" action="{{ route('customer.logout') }}" class="pt-2">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg">Sign Out</button>
                        </form>
                    @else
                        <a href="{{ route('customer.login') }}" class="block px-3 py-2 rounded-lg bg-emerald-600 text-white text-center font-semibold mt-2">Sign In with Mobile</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
