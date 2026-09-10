<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Delhi Greenhouse Hub',
        'code' => 'DEL-GH-01',
        'is_active' => true,
        'is_default' => true,
    ]);
});

test('sitemap.xml returns valid xml containing public pages and excluding private paths', function () {
    $activeCat = Category::factory()->create([
        'name' => 'Flowering Indoor Beauties',
        'slug' => 'flowering-indoor-beauties',
        'is_active' => true,
    ]);

    $activeBrand = Brand::factory()->create([
        'name' => 'Botanical Delhi Exotics',
        'slug' => 'botanical-delhi-exotics',
        'is_active' => true,
    ]);

    $activeProduct = Product::factory()->create([
        'name' => 'Rare Variegated Monstera',
        'slug' => 'rare-variegated-monstera',
        'is_active' => true,
        'brand_id' => $activeBrand->id,
    ]);
    $activeProduct->categories()->attach($activeCat->id, ['is_primary' => true]);
    ProductVariant::factory()->create([
        'product_id' => $activeProduct->id,
        'is_active' => true,
        'price' => 1499.00,
    ]);

    $inactiveProduct = Product::factory()->create([
        'name' => 'Hidden Secret Plant',
        'slug' => 'hidden-secret-plant',
        'is_active' => false,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $inactiveProduct->id,
        'is_active' => true,
    ]);

    $response = $this->get('/sitemap.xml');
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $content = $response->getContent();

    expect($content)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
        ->toContain(route('home'))
        ->toContain(route('shop.index'))
        ->toContain(route('categories.show', $activeCat->slug))
        ->toContain(route('brands.show', $activeBrand->slug))
        ->toContain(route('products.show', $activeProduct->slug))
        ->not->toContain('hidden-secret-plant')
        ->not->toContain('/checkout')
        ->not->toContain('/cart')
        ->not->toContain('/account')
        ->not->toContain('/admin')
        ->not->toContain('/login');
});

test('robots.txt disallows private routes and references sitemap.xml', function () {
    $robotsPath = public_path('robots.txt');
    expect(file_exists($robotsPath))->toBeTrue();

    $content = file_get_contents($robotsPath);
    expect($content)->toContain('Disallow: /cart')
        ->toContain('Disallow: /checkout')
        ->toContain('Disallow: /account')
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /login')
        ->toContain('Sitemap: /sitemap.xml');
});

test('skip to main content link and target landmark are present on storefront layouts', function () {
    $response = $this->get(route('home'));
    $response->assertOk();
    $response->assertSee('href="#main-content"', false);
    $response->assertSee('id="main-content"', false);
    $response->assertSee('Skip to main content');

    $shopResponse = $this->get(route('shop.index'));
    $shopResponse->assertOk();
    $shopResponse->assertSee('href="#main-content"', false);
    $shopResponse->assertSee('id="main-content"', false);
});

test('catalog page has exactly one main landmark element', function () {
    $response = $this->get(route('shop.index'));
    $response->assertOk();

    $content = $response->getContent();
    // Count <main occurrences
    $mainOpenMatches = preg_match_all('/<main[\s>]/i', $content);
    $mainCloseMatches = preg_match_all('/<\/main>/i', $content);

    expect($mainOpenMatches)->toBe(1)
        ->and($mainCloseMatches)->toBe(1);
});

test('product detail page includes high priority image and accessible touch controls', function () {
    $product = Product::factory()->create([
        'name' => 'Fiddle Leaf Fig Premium',
        'slug' => 'fiddle-leaf-fig-premium',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
        'price' => 1250.00,
    ]);
    ProductImage::factory()->create([
        'product_id' => $product->id,
        'file_path' => 'products/fiddle.jpg',
        'is_primary' => true,
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();

    // Check fetchpriority="high" on PDP hero image
    $response->assertSee('fetchpriority="high"', false);

    // Check accessible buttons
    $response->assertSee('aria-label="Decrease quantity"', false);
    $response->assertSee('aria-label="Increase quantity"', false);
    $response->assertSee('aria-label="Item quantity"', false);

    // Check 3-day Delhi NCR delivery rule
    $response->assertSee('ONLY within Delhi NCR', false);
    $response->assertSee('delivered within 3 days', false);
    $response->assertSee('FREE delivery', false);
    $response->assertDontSee('₹99');
});

test('product card includes explicit dimensions to prevent layout shifts', function () {
    $product = Product::factory()->create([
        'name' => 'Areca Palm Green Air Purifier',
        'slug' => 'areca-palm-green-air-purifier',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
        'price' => 850.00,
    ]);
    ProductImage::factory()->create([
        'product_id' => $product->id,
        'file_path' => 'products/areca.jpg',
        'is_primary' => true,
    ]);

    $response = $this->get(route('shop.index'));
    $response->assertOk();

    $response->assertSee('width="400"', false);
    $response->assertSee('height="400"', false);
    $response->assertSee('aria-label="View Areca Palm Green Air Purifier"', false);
});

test('storefront css includes prefers-reduced-motion media query and touch scrolling', function () {
    $cssPath = public_path('css/storefront.css');
    expect(file_exists($cssPath))->toBeTrue();

    $content = file_get_contents($cssPath);
    expect($content)->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('.touch-scroll')
        ->toContain(':focus-visible');
});
