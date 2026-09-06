<?php

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Storefront Cart Hub',
        'code' => 'WH-CART',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->product = Product::factory()->create(['is_active' => true]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price' => '350.00',
        'is_active' => true,
    ]);

    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 25,
        'reserved_quantity' => 0,
        'safety_stock' => 0,
    ]);
});

test('guest can add product to cart via standard form submission', function () {
    $response = $this->from(route('products.show', $this->product->slug))->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    $response->assertRedirect(route('products.show', $this->product->slug));
    $response->assertSessionHas('success');

    $cartPage = $this->get(route('cart.index'));
    $cartPage->assertOk();
    $cartPage->assertSee($this->product->name);
    $cartPage->assertSee('700.00'); // 2 * 350.00
});

test('customer can add product to cart when authenticated', function () {
    $customer = Customer::factory()->create(['is_active' => true]);
    $this->actingAs($customer, 'customer');

    $response = $this->from(route('products.show', $this->product->slug))->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 3,
    ]);

    $response->assertRedirect(route('products.show', $this->product->slug));
    $response->assertSessionHas('success');

    $cartPage = $this->get(route('cart.index'));
    $cartPage->assertOk();
    $cartPage->assertSee('1,050.00'); // 3 * 350.00
});

test('storefront cart strictly rejects client-side price tampering', function () {
    // Malicious user sends a fake cheap price in request payload
    $response = $this->from(route('products.show', $this->product->slug))->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
        'price' => '1.00',
        'subtotal' => '1.00',
    ]);

    $response->assertRedirect(route('products.show', $this->product->slug));

    // Server-authoritative price must be 350.00, NOT 1.00
    $cartPage = $this->get(route('cart.index'));
    $cartPage->assertOk();
    $cartPage->assertSee('350.00');
    $cartPage->assertDontSee('1.00 each');
});

test('storefront cart enforces maximum quantity clamp of 50 units', function () {
    // Warehouse has 100 in stock
    $this->inventory->update(['quantity' => 100]);

    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 51,
    ]);

    $response->assertSessionHasErrors(['quantity']);
});

test('storefront cart rejects adding out of stock variant', function () {
    $this->inventory->update(['quantity' => 0]);

    $response = $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $response->assertSessionHasErrors();
});

test('storefront cart displays live inventory notice that items are unreserved', function () {
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $response = $this->get(route('cart.index'));
    $response->assertOk();
    $response->assertSee('Items in cart are not reserved');
});
