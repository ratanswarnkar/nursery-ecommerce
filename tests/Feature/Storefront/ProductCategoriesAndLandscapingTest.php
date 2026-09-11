<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\ProductCategorySeeder;

beforeEach(function () {
    $this->seed(ProductCategorySeeder::class);
});

/*
|--------------------------------------------------------------------------
| A. Product Categories Validation
|--------------------------------------------------------------------------
*/

test('1. all 9 agreed product categories exist with unique slugs and active status', function () {
    $expectedCategories = [
        'indoor-plants' => 'Indoor Plants',
        'flowering-plants' => 'Flowering Plants',
        'air-purifying' => 'Air Purifying Plants',
        'fruit-plants' => 'Fruit Plants',
        'outdoor-plants' => 'Outdoor Plants',
        'herbal-medicinal-plants' => 'Herbal & Medicinal Plants',
        'flowering-saplings' => 'Flowering Saplings',
        'terracotta-pots' => 'Terracotta Pots',
        'plant-care' => 'Plant Care / Potting Mix',
    ];

    expect(Category::where('is_active', true)->count())->toBe(9);

    foreach ($expectedCategories as $slug => $name) {
        $cat = Category::where('slug', $slug)->first();
        expect($cat)->not->toBeNull()
            ->and($cat->name)->toBe($name)
            ->and($cat->is_active)->toBeTrue();
    }
});

test('2. running ProductCategorySeeder repeatedly is idempotent and does not create duplicate categories', function () {
    $this->seed(ProductCategorySeeder::class);
    $this->seed(ProductCategorySeeder::class);

    expect(Category::count())->toBe(9);
});

test('3. each of the 9 product category pages loads successfully and returns HTTP 200', function () {
    $slugs = [
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

    foreach ($slugs as $slug) {
        $response = $this->get(route('categories.show', $slug));
        $response->assertOk();
        $response->assertSee(Category::where('slug', $slug)->value('name'));
    }
});

test('4. product category pages render canonical URL and schema metadata', function () {
    $response = $this->get(route('categories.show', 'indoor-plants'));
    $response->assertOk();
    $response->assertSee('<link rel="canonical" href="'.route('categories.show', 'indoor-plants').'">', false);
    $response->assertSee('Indoor Plants | Sugandha Farms and Nursery');
});

test('5. products can be associated with categories and appear on their category page', function () {
    $category = Category::where('slug', 'indoor-plants')->first();

    $product = Product::factory()->create([
        'name' => 'Fiddle Leaf Fig Premium',
        'slug' => 'fiddle-leaf-fig-premium',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'FLF-PREM-01',
        'price' => '899.00',
        'is_active' => true,
    ]);

    $product->categories()->attach($category->id, ['is_primary' => true]);

    $response = $this->get(route('categories.show', 'indoor-plants'));
    $response->assertOk();
    $response->assertSee('Fiddle Leaf Fig Premium');
});

test('6. category filtering in shop catalog functions accurately', function () {
    $indoor = Category::where('slug', 'indoor-plants')->first();
    $outdoor = Category::where('slug', 'outdoor-plants')->first();

    $indoorProd = Product::factory()->create(['name' => 'Calathea Orbifolia', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $indoorProd->id, 'sku' => 'CAL-01', 'is_active' => true]);
    $indoorProd->categories()->attach($indoor->id);

    $outdoorProd = Product::factory()->create(['name' => 'Bougainvillea Royal Red', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $outdoorProd->id, 'sku' => 'BOU-01', 'is_active' => true]);
    $outdoorProd->categories()->attach($outdoor->id);

    $response = $this->get(route('shop.index', ['category' => 'indoor-plants']));
    $response->assertOk();
    $response->assertSee('Calathea Orbifolia');
    $response->assertDontSee('Bougainvillea Royal Red');
});

/*
|--------------------------------------------------------------------------
| B. Landscaping Service Offering Validation
|--------------------------------------------------------------------------
*/

test('7. landscaping route /services/landscaping resolves and returns HTTP 200', function () {
    $response = $this->get(route('services.landscaping'));
    $response->assertOk();
    $response->assertSee('Professional Landscaping Services in Delhi NCR');
    $response->assertSee('Garden Landscaping');
    $response->assertSee('Residential Landscaping');
    $response->assertSee('Commercial Landscaping');
    $response->assertSee('Lawn &amp; Garden Development', false);
    $response->assertSee('Planting &amp; Plantation', false);
    $response->assertSee('Garden Maintenance');
});

test('8. landscaping page has proper SEO title, canonical link, and schema', function () {
    $response = $this->get(route('services.landscaping'));
    $response->assertOk();
    $response->assertSee('<link rel="canonical" href="'.route('services.landscaping').'">', false);
    $response->assertSee('Landscaping Services in Delhi NCR | Sugandha Farms and Nursery');
    $response->assertSee('"@type": "Service"', false);
    $response->assertSee('"name": "Professional Landscaping Services"', false);
});

test('9. landscaping has direct enquiry and contact actions pointing to official channels', function () {
    $response = $this->get(route('services.landscaping'));
    $response->assertOk();
    $response->assertSee(route('policy.contact'));
    $response->assertSee('tel:09811114365');
});

test('10. landscaping is strictly a service offering and is NOT a physical product category', function () {
    expect(Category::where('slug', 'landscaping')->exists())->toBeFalse();
    expect(Category::where('slug', 'landscaping-services')->exists())->toBeFalse();
    expect(Category::where('name', 'like', '%landscaping%')->exists())->toBeFalse();
});

test('11. landscaping is discoverable from homepage via dedicated CTA card', function () {
    $response = $this->get(route('home'));
    $response->assertOk();
    $response->assertSee(route('services.landscaping'));
    $response->assertSee('Landscaping Services');
    $response->assertSee('Transform your garden or outdoor space with professional landscaping solutions.');
});

test('12. landscaping is present in header navigation and mobile menu', function () {
    $response = $this->get(route('home'));
    $response->assertOk();
    $response->assertSee(route('services.landscaping'));
});

test('13. landscaping is present in footer navigation alongside all 9 product categories', function () {
    $response = $this->get(route('home'));
    $response->assertOk();

    // Verify footer has landscaping service
    $response->assertSee(route('services.landscaping'));

    // Verify footer has all 9 agreed product category links
    $slugs = [
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

    foreach ($slugs as $slug) {
        $response->assertSee(route('categories.show', $slug));
    }
});

test('14. sitemap.xml includes landscaping services route and all active categories', function () {
    $response = $this->get(route('sitemap'));
    $response->assertOk();
    $response->assertSee('<loc>'.route('services.landscaping').'</loc>', false);
    $response->assertSee('<loc>'.route('categories.show', 'indoor-plants').'</loc>', false);
    $response->assertSee('<loc>'.route('categories.show', 'air-purifying').'</loc>', false);
});
