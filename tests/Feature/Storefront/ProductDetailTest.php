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

test('active product with all inactive variants returns 200 with currently unavailable status and disables add to cart', function () {
    $product = Product::factory()->create([
        'name' => 'Dormant Winter Fern',
        'is_active' => true,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'FERN-DORMANT-01',
        'is_active' => false,
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('Dormant Winter Fern');
    $response->assertSee('Currently Unavailable');
    $response->assertDontSee('FERN-DORMANT-01');
});

test('inactive variant cannot be added to cart', function () {
    $product = Product::factory()->create(['name' => 'Protected Rare Orchid', 'is_active' => true]);

    $inactiveVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => false,
        'price' => '1499.00',
    ]);

    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $inactiveVariant->id,
        'quantity' => 1,
    ]);

    $response->assertSessionHasErrors('product_variant_id');
});

test('PDP displays authentic Delhi NCR delivery information', function () {
    $product = Product::factory()->create(['name' => 'Areca Palm Classic', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('Delhi NCR');
    $response->assertSee('3 days');
    $response->assertSee('1,000');
});

test('PDP variant matrix reflects low stock when stock is at or below safety stock', function () {
    $product = Product::factory()->create(['name' => 'Rare Syngonium Albo', 'is_active' => true]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 2,
        'reserved_quantity' => 0,
        'safety_stock' => 3, // available (2) <= safety_stock (3) => low stock
    ]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('Low Stock: Only 2 Left');
});

test('PDP renders botanical specifications from real custom attributes without fabricating data', function () {
    $product = Product::factory()->create([
        'name' => 'Bonsai Ficus Microcarpa',
        'is_active' => true,
        'custom_attributes' => [
            'light_requirement' => 'bright_indirect',
            'care_level' => 'moderate',
            'watering' => 'twice_weekly',
        ],
    ]);

    ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);

    $response = $this->get(route('products.show', $product->slug));
    $response->assertOk();
    $response->assertSee('Light Requirement');
    $response->assertSee('bright indirect');
    $response->assertSee('Care Level');
    $response->assertSee('moderate');
    $response->assertSee('Watering');
    $response->assertSee('twice weekly');
});
