<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Default Nursery Hub',
        'code' => 'WH-DEF',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->product = Product::factory()->create(['is_active' => true]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price' => '249.50',
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);
});

test('guest can view empty cart', function () {
    $response = $this->get(route('cart.index'));
    $response->assertOk();
    $response->assertSee('Your cart is empty');
});

test('guest can add active variant to cart and price is resolved server-side', function () {
    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
        'price' => '1.00', // Tampering attempt: must be completely ignored
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $cart = Cart::where('is_active', true)->first();
    expect($cart)->not->toBeNull();

    $item = CartItem::where('cart_id', $cart->id)->first();
    expect($item)->not->toBeNull()
        ->and($item->quantity)->toBe(2);

    $cartView = $this->get(route('cart.index'));
    $cartView->assertOk();
    // Subtotal should be 249.50 * 2 = 499.00
    $cartView->assertSee('499.00');
});

test('guest cannot add more than available stock', function () {
    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 25, // Available is only 20
    ]);

    $response->assertSessionHasErrors('quantity');

    expect(CartItem::count())->toBe(0);
});

test('guest cannot exceed maximum quantity limit of 50 per item', function () {
    // Increase stock to 100
    Inventory::where('product_variant_id', $this->variant->id)->update(['quantity' => 100]);

    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 51,
    ]);

    $response->assertSessionHasErrors('quantity');
    expect(CartItem::count())->toBe(0);
});

test('guest cannot add inactive or deleted variant', function () {
    $inactiveVariant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'is_active' => false,
    ]);

    Inventory::create([
        'product_variant_id' => $inactiveVariant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
    ]);

    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $inactiveVariant->id,
        'quantity' => 1,
    ]);

    $response->assertSessionHasErrors('product_variant_id');
});

test('guest cannot add variant of inactive or deleted product', function () {
    $inactiveProduct = Product::factory()->create(['is_active' => false]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $inactiveProduct->id,
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
    ]);

    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertSessionHasErrors('product_variant_id');
});

test('guest can update item quantity and remove item', function () {
    // Add 2
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    $item = CartItem::first();

    // Update to 5
    $updateResponse = $this->put(route('cart.items.update', $item), [
        'quantity' => 5,
    ]);
    $updateResponse->assertRedirect();
    expect($item->fresh()->quantity)->toBe(5);

    // Remove item
    $deleteResponse = $this->delete(route('cart.items.destroy', $item));
    $deleteResponse->assertRedirect();
    expect(CartItem::find($item->id))->toBeNull();
});

test('guest can clear entire cart', function () {
    $v2 = ProductVariant::factory()->create(['product_id' => $this->product->id, 'is_active' => true]);
    Inventory::create([
        'product_variant_id' => $v2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
    ]);

    $this->post(route('cart.items.store'), ['product_variant_id' => $this->variant->id, 'quantity' => 1]);
    $this->post(route('cart.items.store'), ['product_variant_id' => $v2->id, 'quantity' => 2]);

    expect(CartItem::count())->toBe(2);

    $clearResponse = $this->post(route('cart.clear'));
    $clearResponse->assertRedirect();

    expect(CartItem::count())->toBe(0);
});
