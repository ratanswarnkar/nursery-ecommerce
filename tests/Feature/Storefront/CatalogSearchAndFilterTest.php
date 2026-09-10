<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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

test('search query finds product by full_description', function () {
    $matched = Product::factory()->create([
        'name' => 'Foliage Marvel',
        'full_description' => 'Remarkable chlorophyll rich velvet texture botanical specimen.',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create(['product_id' => $matched->id, 'is_active' => true]);

    $unmatched = Product::factory()->create([
        'name' => 'Ordinary Cactus',
        'full_description' => 'Prickly desert succulent needing harsh sun.',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create(['product_id' => $unmatched->id, 'is_active' => true]);

    $response = $this->get(route('shop.index', ['q' => 'chlorophyll']));
    $response->assertOk();
    $response->assertSee('Foliage Marvel');
    $response->assertDontSee('Ordinary Cactus');
});

test('search query finds product by brand name and category name', function () {
    $brand = Brand::factory()->create(['name' => 'Himalayan Organic Nursery', 'is_active' => true]);
    $category = Category::factory()->create(['name' => 'Rare Ferns Collection', 'is_active' => true]);

    $productWithBrand = Product::factory()->create([
        'name' => 'Silver Pothos',
        'brand_id' => $brand->id,
        'is_active' => true,
    ]);
    ProductVariant::factory()->create(['product_id' => $productWithBrand->id, 'is_active' => true]);

    $productWithCat = Product::factory()->create([
        'name' => 'Staghorn Fern',
        'is_active' => true,
    ]);
    $productWithCat->categories()->attach($category->id, ['is_primary' => true]);
    ProductVariant::factory()->create(['product_id' => $productWithCat->id, 'is_active' => true]);

    // Search by brand name
    $brandSearch = $this->get(route('shop.index', ['q' => 'Himalayan Organic']));
    $brandSearch->assertOk();
    $brandSearch->assertSee('Silver Pothos');

    // Search by category name
    $catSearch = $this->get(route('shop.index', ['q' => 'Rare Ferns']));
    $catSearch->assertOk();
    $catSearch->assertSee('Staghorn Fern');
});

test('brand filter restricts products correctly using checkbox parameter array', function () {
    $brandA = Brand::factory()->create(['name' => 'Vedic Botanicals', 'slug' => 'vedic-botanicals', 'is_active' => true]);
    $brandB = Brand::factory()->create(['name' => 'Green Earth Nursery', 'slug' => 'green-earth', 'is_active' => true]);

    $prodA = Product::factory()->create(['name' => 'Vedic Tulsi Plant', 'brand_id' => $brandA->id, 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $prodA->id, 'is_active' => true]);

    $prodB = Product::factory()->create(['name' => 'Earth Money Plant', 'brand_id' => $brandB->id, 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $prodB->id, 'is_active' => true]);

    $response = $this->get(route('shop.index', ['brand' => ['vedic-botanicals']]));
    $response->assertOk();
    $response->assertSee('Vedic Tulsi Plant');
    $response->assertDontSee('Earth Money Plant');

    // Also verify single string parameter is normalized safely
    $responseSingle = $this->get(route('shop.index', ['brand' => 'green-earth']));
    $responseSingle->assertOk();
    $responseSingle->assertSee('Earth Money Plant');
    $responseSingle->assertDontSee('Vedic Tulsi Plant');
});

test('empty search results page renders helpful guidance and reset CTAs', function () {
    $response = $this->get(route('shop.index', ['q' => 'NonExistentBotanicalSpecies12345']));
    $response->assertOk();
    $response->assertSee('No products found matching');
    $response->assertSee('NonExistentBotanicalSpecies12345');
    $response->assertSee('Suggestions to help find your plant:');
    $response->assertSee('Clear All Filters');
    $response->assertSee('Browse All Plants');
});
