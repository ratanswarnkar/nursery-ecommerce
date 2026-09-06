<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Main Botanical Warehouse',
        'code' => 'WH-MAIN',
        'is_active' => true,
        'is_default' => true,
    ]);
});

test('storefront home loads and returns successful response', function () {
    $response = $this->get(route('home'));
    $response->assertOk();
    $response->assertSee('Living Greenery');
});

test('active products with active variants are listed in shop catalog', function () {
    $product = Product::factory()->create([
        'name' => 'Monstera Deliciosa',
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '499.00',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop.index'));
    $response->assertOk();
    $response->assertSee('Monstera Deliciosa');
});

test('inactive products are excluded from storefront catalog', function () {
    $activeProduct = Product::factory()->create(['name' => 'Active Ficus', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $activeProduct->id, 'is_active' => true]);

    $inactiveProduct = Product::factory()->create(['name' => 'Inactive Fern', 'is_active' => false]);
    ProductVariant::factory()->create(['product_id' => $inactiveProduct->id, 'is_active' => true]);

    $response = $this->get(route('shop.index'));
    $response->assertOk();
    $response->assertSee('Active Ficus');
    $response->assertDontSee('Inactive Fern');
});

test('soft deleted products are excluded from storefront catalog', function () {
    $product = Product::factory()->create(['name' => 'Deleted Palm', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);
    $product->delete();

    $response = $this->get(route('shop.index'));
    $response->assertOk();
    $response->assertDontSee('Deleted Palm');
});

test('products without active variants are excluded from catalog', function () {
    $product = Product::factory()->create(['name' => 'Variantless Bamboo', 'is_active' => true]);
    // Only inactive variant
    ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => false]);

    $response = $this->get(route('shop.index'));
    $response->assertOk();
    $response->assertDontSee('Variantless Bamboo');
});

test('inactive or soft deleted category returns 404', function () {
    $inactiveCat = Category::factory()->create(['is_active' => false, 'slug' => 'hidden-shrubs']);
    $response1 = $this->get(route('categories.show', $inactiveCat->slug));
    $response1->assertNotFound();

    $deletedCat = Category::factory()->create(['is_active' => true, 'slug' => 'trashed-herbs']);
    $deletedCat->delete();
    $response2 = $this->get(route('categories.show', $deletedCat->slug));
    $response2->assertNotFound();
});

test('inactive or soft deleted brand returns 404', function () {
    $inactiveBrand = Brand::factory()->create(['is_active' => false, 'slug' => 'hidden-grower']);
    $response1 = $this->get(route('brands.show', $inactiveBrand->slug));
    $response1->assertNotFound();

    $deletedBrand = Brand::factory()->create(['is_active' => true, 'slug' => 'trashed-grower']);
    $deletedBrand->delete();
    $response2 = $this->get(route('brands.show', $deletedBrand->slug));
    $response2->assertNotFound();
});

test('search query safely filters catalog by name, sku, and description', function () {
    $matchProduct = Product::factory()->create([
        'name' => 'Sansevieria Trifasciata Snake Plant',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create(['product_id' => $matchProduct->id, 'is_active' => true]);

    $otherProduct = Product::factory()->create([
        'name' => 'Peace Lily Spathiphyllum',
        'is_active' => true,
    ]);
    ProductVariant::factory()->create(['product_id' => $otherProduct->id, 'is_active' => true]);

    $response = $this->get(route('shop.index', ['q' => 'Snake Plant']));
    $response->assertOk();
    $response->assertSee('Sansevieria Trifasciata');
    $response->assertDontSee('Peace Lily');

    // Safe handling of special characters & SQL wildcards
    $safeResponse = $this->get(route('shop.index', ['q' => "Snake%'_'; DROP TABLE products;--"]));
    $safeResponse->assertOk();
});

test('sort parameter only accepts whitelist and safely falls back', function () {
    $p1 = Product::factory()->create(['name' => 'Alpha Aloe', 'is_active' => true]);
    $v1 = ProductVariant::factory()->create(['product_id' => $p1->id, 'price' => '100.00', 'is_active' => true]);

    $p2 = Product::factory()->create(['name' => 'Zeta Zamioculcas', 'is_active' => true]);
    $v2 = ProductVariant::factory()->create(['product_id' => $p2->id, 'price' => '500.00', 'is_active' => true]);

    // Price asc
    $resAsc = $this->get(route('shop.index', ['sort' => 'price_asc']));
    $resAsc->assertOk();

    // Price desc
    $resDesc = $this->get(route('shop.index', ['sort' => 'price_desc']));
    $resDesc->assertOk();

    // Unknown sort safely falls back to featured without error
    $resFallback = $this->get(route('shop.index', ['sort' => 'malicious_col;--']));
    $resFallback->assertOk();
});

test('price range filter correctly filters products within bounds', function () {
    $cheapProduct = Product::factory()->create(['name' => 'Affordable Succulent', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $cheapProduct->id, 'price' => '150.00', 'is_active' => true]);

    $priceyProduct = Product::factory()->create(['name' => 'Rare Variegated Monstera', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $priceyProduct->id, 'price' => '1200.00', 'is_active' => true]);

    $response = $this->get(route('shop.index', ['min_price' => 100, 'max_price' => 300]));
    $response->assertOk();
    $response->assertSee('Affordable Succulent');
    $response->assertDontSee('Rare Variegated Monstera');
});

test('stock availability filter respects live inventory quantities', function () {
    $inStockProduct = Product::factory()->create(['name' => 'In Stock Jade', 'is_active' => true]);
    $vInStock = ProductVariant::factory()->create(['product_id' => $inStockProduct->id, 'is_active' => true]);
    Inventory::create([
        'product_variant_id' => $vInStock->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'safety_stock' => 0,
    ]);

    $outOfStockProduct = Product::factory()->create(['name' => 'Depleted Bonsai', 'is_active' => true]);
    $vOutOfStock = ProductVariant::factory()->create(['product_id' => $outOfStockProduct->id, 'is_active' => true]);
    Inventory::create([
        'product_variant_id' => $vOutOfStock->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 0,
        'reserved_quantity' => 0,
        'safety_stock' => 0,
    ]);

    $response = $this->get(route('shop.index', ['in_stock' => '1']));
    $response->assertOk();
    $response->assertSee('In Stock Jade');
    $response->assertDontSee('Depleted Bonsai');
});

test('category filter recursively includes products from subcategories', function () {
    $parentCat = Category::factory()->create(['name' => 'Indoor Plants', 'is_active' => true]);
    $childCat = Category::factory()->create(['name' => 'Low Light Indoor', 'parent_id' => $parentCat->id, 'is_active' => true]);

    $product = Product::factory()->create(['name' => 'Cast Iron Plant', 'is_active' => true]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);
    $product->categories()->attach($childCat->id, ['is_primary' => true]);

    // Querying parent category includes child category's product
    $response = $this->get(route('categories.show', $parentCat->slug));
    $response->assertOk();
    $response->assertSee('Cast Iron Plant');
});

test('attribute filtering follows strict AND across dimensions and OR within dimension', function () {
    // Dimension 1: Sunlight
    $sunlightAttr = Attribute::create(['name' => 'Sunlight', 'code' => 'sunlight', 'type' => 'select', 'is_filterable' => true, 'is_active' => true]);
    $valFullSun = AttributeValue::create(['attribute_id' => $sunlightAttr->id, 'value' => 'Full Sun', 'label' => 'Full Sun']);
    $valPartialShade = AttributeValue::create(['attribute_id' => $sunlightAttr->id, 'value' => 'Partial Shade', 'label' => 'Partial Shade']);

    // Dimension 2: Pot Size
    $potSizeAttr = Attribute::create(['name' => 'Pot Size', 'code' => 'pot_size', 'type' => 'select', 'is_filterable' => true, 'is_active' => true]);
    $val10Inch = AttributeValue::create(['attribute_id' => $potSizeAttr->id, 'value' => '10 inch', 'label' => '10 inch']);
    $val6Inch = AttributeValue::create(['attribute_id' => $potSizeAttr->id, 'value' => '6 inch', 'label' => '6 inch']);

    // Product A: Full Sun + 10 inch (Matches target)
    $pA = Product::factory()->create(['name' => 'Plant Alpha', 'is_active' => true]);
    $vA = ProductVariant::factory()->create(['product_id' => $pA->id, 'is_active' => true]);
    $vA->attributeValues()->attach([$valFullSun->id, $val10Inch->id]);

    // Product B: Full Sun + 6 inch (Matches Sunlight, but NOT 10 inch)
    $pB = Product::factory()->create(['name' => 'Plant Beta', 'is_active' => true]);
    $vB = ProductVariant::factory()->create(['product_id' => $pB->id, 'is_active' => true]);
    $vB->attributeValues()->attach([$valFullSun->id, $val6Inch->id]);

    // Product C: Partial Shade + 10 inch (Matches target via OR in Sunlight)
    $pC = Product::factory()->create(['name' => 'Plant Gamma', 'is_active' => true]);
    $vC = ProductVariant::factory()->create(['product_id' => $pC->id, 'is_active' => true]);
    $vC->attributeValues()->attach([$valPartialShade->id, $val10Inch->id]);

    // Query: (Sunlight = Full Sun OR Partial Shade) AND (Pot Size = 10 inch)
    $response = $this->get(route('shop.index', [
        'attributes' => [
            $sunlightAttr->id => [$valFullSun->id, $valPartialShade->id],
            $potSizeAttr->id => [$val10Inch->id],
        ],
    ]));

    $response->assertOk();
    $response->assertSee('Plant Alpha');
    $response->assertSee('Plant Gamma');
    $response->assertDontSee('Plant Beta');
});

test('non-filterable or invalid attribute filters are rejected safely', function () {
    $unfilterable = Attribute::create(['name' => 'Internal Note', 'code' => 'note', 'type' => 'text', 'is_filterable' => false, 'is_active' => true]);
    $val = AttributeValue::create(['attribute_id' => $unfilterable->id, 'value' => 'Secret', 'label' => 'Secret']);

    $product = Product::factory()->create(['name' => 'Standard Palm', 'is_active' => true]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);
    $variant->attributeValues()->attach($val->id);

    // Attempting to filter by non-filterable attribute must ignore the filter safely
    $response = $this->get(route('shop.index', [
        'attributes' => [
            $unfilterable->id => [$val->id],
        ],
    ]));
    $response->assertOk();
    $response->assertSee('Standard Palm');
});
