<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'GreenLife Botanical Nursery') - Healthy Plants & Garden Essentials</title>
    <meta name="description" content="@yield('meta_description', 'Discover fresh greenhouse-grown indoor plants, flowering saplings, fruit trees, premium terracotta planters, organic soils, and expert gardening care.')">

    @if(!empty($canonicalUrl))
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif

    @yield('meta_robots')

    <!-- OpenGraph / Social Meta -->
    <meta property="og:site_name" content="GreenLife Nursery">
    <meta property="og:title" content="@yield('og_title', View::getSection('title', 'GreenLife Botanical Nursery'))">
    <meta property="og:description" content="@yield('og_description', View::getSection('meta_description', 'Healthy Plants, Saplings & Garden Care'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-nursery-default.jpg'))">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

    <!-- Styles & Tailwind -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.17/dist/tailwind.min.css">
    @endif

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
    </style>

    <!-- Alpine.js (Lightweight reactive UI interactions) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @yield('seo')
    @stack('head')
</head>
<body class="flex flex-col min-h-full antialiased bg-slate-50 text-slate-900" x-data="{ mobileMenuOpen: false, cartOpen: false }">

    <!-- Universal Announcement Bar -->
    <div class="bg-emerald-950 text-emerald-100 text-xs py-2 px-4 border-b border-emerald-900/60">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-1.5 sm:gap-4 text-center sm:text-left">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center bg-emerald-700 text-white rounded-full w-4 h-4 text-[10px] font-bold">&check;</span>
                <span class="font-medium tracking-wide">100% Transit Safe Botanical Packaging &bull; Healthy Live Plant Guarantee</span>
            </div>
            <div class="flex items-center gap-4 text-emerald-200/90 text-[11px]">
                <span>Helpline: <strong class="text-white">+91 98765 43210</strong></span>
                <span class="hidden md:inline">&bull;</span>
                <span class="hidden md:inline">Express Dispatch from Delhi Greenhouse</span>
            </div>
        </div>
    </div>

    <!-- Storefront Header Navigation -->
    @include('components.storefront.header')

    <!-- Flash Toasts / Notifications -->
    @include('components.storefront.flash-toast')

    <!-- Main Content Slot -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Storefront Footer -->
    @include('components.storefront.footer')

    @stack('scripts')
</body>
</html>
