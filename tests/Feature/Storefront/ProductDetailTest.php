<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Default PDP Hub',
        'code' => 'WH-PDP',
        'is_active' => true,
        'is_default' => true,
    ]);
});

test('active product loads PDP with details, active variants, and canonical URL', function () {
    $product = Product::factory()->create([
        'name' => 'Monstera Adansonii',
        'is_active' => true,
        'short_description' => 'Swiss cheese vine with natural fenestrations.',
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '399.00',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('Monstera Adansonii');
    $response->assertSee('399.00');
    $response->assertSee(route('products.show', $product->slug));
});

test('inactive or soft deleted product returns 404', function () {
    $inactiveProduct = Product::factory()->create(['is_active' => false, 'slug' => 'hidden-ivy']);
    ProductVariant::factory()->create(['product_id' => $inactiveProduct->id, 'is_active' => true]);

    $response1 = $this->get(route('products.show', $inactiveProduct->slug));
    $response1->assertNotFound();

    $deletedProduct = Product::factory()->create(['is_active' => true, 'slug' => 'deleted-fern']);
    ProductVariant::factory()->create(['product_id' => $deletedProduct->id, 'is_active' => true]);
    $deletedProduct->delete();

    $response2 = $this->get(route('products.show', $deletedProduct->slug));
    $response2->assertNotFound();
});

test('PDP excludes inactive or soft deleted variants from variant matrix', function () {
    $product = Product::factory()->create(['name' => 'Calathea Orbifolia', 'is_active' => true]);

    $activeVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'CAL-ACTIVE-01',
        'is_active' => true,
    ]);

    $inactiveVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'CAL-INACTIVE-02',
        'is_active' => false,
    ]);

    $deletedVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'CAL-DELETED-03',
        'is_active' => true,
    ]);
    $deletedVariant->delete();

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('CAL-ACTIVE-01');
    $response->assertDontSee('CAL-INACTIVE-02');
    $response->assertDontSee('CAL-DELETED-03');
});

test('PDP variant matrix contains live available stock and disables out of stock variants', function () {
    $product = Product::factory()->create(['name' => 'Fiddle Leaf Fig', 'is_active' => true]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 0,
        'reserved_quantity' => 0,
        'safety_stock' => 0,
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('Out of Stock');
});

test('PDP renders Schema.org JSON-LD structured data', function () {
    $product = Product::factory()->create(['name' => 'Golden Pothos', 'is_active' => true]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '249.00',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('"@type": "Product"', false);
    $response->assertSee('"@type": "Offer"', false);
    $response->assertSee('"priceCurrency": "INR"', false);
});
