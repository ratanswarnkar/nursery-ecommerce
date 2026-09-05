<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tender;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Storage Warehouse',
        'code' => 'WH-STORE',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->product = Product::factory()->create(['is_active' => true, 'name' => 'Bonsai Ficus']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'BON-FIC-01',
        'price' => '450.00',
        'is_active' => true,
    ]);

    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'safety_stock' => 1,
    ]);
});

test('cart detects stock degradation in real time when stock is reduced after cart addition', function () {
    // 1. Add 5 items while 10 are available
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 5,
    ]);

    // 2. Reduce warehouse stock to 3 (less than cart quantity of 5)
    $this->inventory->update(['quantity' => 3]);

    // 3. View cart - must report insufficient stock
    $response = $this->get(route('cart.index'));
    $response->assertOk();
    $response->assertSee('Stock Alert:');
    $response->assertSee('Only 3 available in stock');

    // JSON API response
    $jsonResponse = $this->getJson(route('cart.index'));
    $jsonResponse->assertOk()
        ->assertJson([
            'has_issues' => true,
            'items' => [
                [
                    'status' => 'insufficient_stock',
                    'is_purchasable' => false,
                    'available_stock' => 3,
                ],
            ],
        ]);
});

test('cart detects stock depletion when item becomes out of stock', function () {
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    // Stock depleted to 0
    $this->inventory->update(['quantity' => 0]);

    $jsonResponse = $this->getJson(route('cart.index'));
    $jsonResponse->assertOk()
        ->assertJson([
            'has_issues' => true,
            'items' => [
                [
                    'status' => 'out_of_stock',
                    'is_purchasable' => false,
                    'available_stock' => 0,
                ],
            ],
        ]);
});

test('cart marks item unavailable if variant or product is deactivated or deleted', function () {
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    // Deactivate the product
    $this->product->update(['is_active' => false]);

    $jsonResponse = $this->getJson(route('cart.index'));
    $jsonResponse->assertOk()
        ->assertJson([
            'has_issues' => true,
            'items' => [
                [
                    'status' => 'unavailable',
                    'is_purchasable' => false,
                ],
            ],
        ]);
});

test('guest session IDOR isolation prevents tampering with another guest cart', function () {
    // Session 1 creates a cart item
    $cart1 = Cart::create([
        'session_id' => 'session-alpha-11111',
        'is_active' => true,
    ]);
    $item1 = CartItem::create([
        'cart_id' => $cart1->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    // Session 2 (current request with fresh session) tries to mutate session 1's item
    $updateResponse = $this->put(route('cart.items.update', $item1), [
        'quantity' => 5,
    ]);
    $updateResponse->assertNotFound();

    expect($item1->fresh()->quantity)->toBe(2);

    $deleteResponse = $this->delete(route('cart.items.destroy', $item1));
    $deleteResponse->assertNotFound();

    expect(CartItem::find($item1->id))->not->toBeNull();
});

test('tender operations remain completely independent from e-commerce inventory and cart', function () {
    // Create a tender
    $tender = Tender::create([
        'tender_number' => 'TND-2026-PH4',
        'name' => 'Municipal Forest Nursery Supply',
        'department_name' => 'City Forest Department',
        'project_name' => 'Urban Greening 2026',
        'original_soq_value' => '500000.00',
        'awarded_value' => '480000.00',
    ]);

    expect($tender)->not->toBeNull();

    // Verify Tender does not affect or reference Cart or CartItem
    expect(Cart::count())->toBe(0)
        ->and(CartItem::count())->toBe(0);

    // Verify variant inventory is unchanged
    expect($this->inventory->fresh()->quantity)->toBe(10)
        ->and($this->inventory->fresh()->reserved_quantity)->toBe(0);
});
