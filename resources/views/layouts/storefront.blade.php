<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @if(!View::hasSection('seo'))
        <title>@yield('title', 'Sugandha Farms and Nursery') - Wholesale Plant Nursery & Garden Essentials</title>
        <meta name="description" content="@yield('meta_description', 'Discover fresh greenhouse-grown indoor plants, flowering saplings, fruit trees, premium terracotta planters, organic soils, and expert gardening care.')">

        @if(!empty($canonicalUrl))
            <link rel="canonical" href="{{ $canonicalUrl }}">
        @else
            <link rel="canonical" href="{{ url()->current() }}">
        @endif

        @yield('meta_robots')
    @endif

    <!-- OpenGraph / Social Meta -->
    <meta property="og:site_name" content="Sugandha Farms and Nursery">
    <meta property="og:title" content="@yield('og_title', View::getSection('title', 'Sugandha Farms and Nursery'))">
    <meta property="og:description" content="@yield('og_description', View::getSection('meta_description', 'Healthy Plants, Saplings & Garden Care'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-nursery-default.jpg'))">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

    <!-- Local Storefront Base & Reliability Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">

    <!-- Styles & Tailwind -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    },
                    colors: {
                        terracotta: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e07a5f',
                            400: '#d96b4f',
                            500: '#cc5a36',
                            600: '#bc4726',
                            700: '#a33719',
                            800: '#872b12',
                            900: '#70220d',
                        },
                        forest: {
                            50: '#f2f7f4',
                            100: '#e1ede6',
                            200: '#c5dcce',
                            300: '#9ec4ae',
                            400: '#6fa687',
                            500: '#4c8a66',
                            600: '#397051',
                            700: '#2e5941',
                            800: '#264735',
                            900: '#1b382b',
                            950: '#0d2018',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }
        [x-cloak] { display: none !important; }
        svg { max-width: 100%; height: auto; }
        .storefront-logo { max-width: 180px; flex-shrink: 0; }
        @media (max-width: 640px) {
            .storefront-logo { max-width: 170px; }
        }
    </style>

    <!-- Alpine.js (Lightweight reactive UI interactions) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @yield('seo')
    @stack('head')
</head>
<body class="flex flex-col min-h-full antialiased bg-slate-50 text-slate-900" x-data="{ mobileMenuOpen: false, cartOpen: false }">
    <!-- Skip to Content Link (WCAG 2.4.1) -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2.5 focus:bg-emerald-800 focus:text-white focus:font-bold focus:text-xs focus:rounded-xl focus:shadow-2xl focus:ring-2 focus:ring-white">
        Skip to main content
    </a>

    <!-- Universal Announcement Bar -->
    <div class="bg-emerald-950 text-emerald-100 text-xs py-2 px-4 border-b border-emerald-900/60">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-1.5 sm:gap-4 text-center sm:text-left">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center bg-emerald-700 text-white rounded-full w-4 h-4 text-[10px] font-bold">&check;</span>
                <span class="font-medium tracking-wide">100% Transit Safe Botanical Packaging &bull; Healthy Live Plant Guarantee</span>
            </div>
            <div class="flex items-center gap-4 text-emerald-200/90 text-[11px]">
                <span>Helpline: <strong class="text-white"><a href="tel:09811114365" class="hover:underline">098111 14365</a></strong></span>
                <span class="hidden md:inline">&bull;</span>
                <span class="hidden md:inline">Wholesale Nursery Dispatch from Delhi</span>
            </div>
        </div>
    </div>

    <!-- Storefront Header Navigation -->
    @include('components.storefront.header')

    <!-- Flash Toasts / Notifications -->
    @include('components.storefront.flash-toast')

    <!-- Main Content Slot -->
    <main id="main-content" tabindex="-1" class="flex-grow focus:outline-hidden">
        @yield('content')
    </main>

    <!-- Storefront Footer -->
    @include('components.storefront.footer')

    @stack('scripts')
</body>
</html>
