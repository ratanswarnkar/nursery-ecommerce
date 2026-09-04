<?php

use App\Models\Admin;
use App\Models\Product;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
});

test('variant price cannot be negative', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'NEG-PRICE-001',
        'price' => '-10.00',
    ]);

    $response->assertSessionHasErrors('price');
});

test('variant price cannot have more than 2 decimal places', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'DEC-PRICE-001',
        'price' => '49.999', // 3 decimal places
    ]);

    $response->assertSessionHasErrors('price');
});

test('compare_at_price must be greater than or equal to selling price', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'CMP-PRICE-001',
        'price' => '500.00',
        'compare_at_price' => '400.00', // compare_at_price < price must be rejected!
    ]);

    $response->assertSessionHasErrors('compare_at_price');
});

test('compare_at_price equal to selling price is allowed', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'CMP-PRICE-002',
        'price' => '500.00',
        'compare_at_price' => '500.00',
    ]);

    $response->assertRedirect(route('admin.products.variants.index', $product));
});

test('cost_price is allowed to exceed selling price for clearance items', function () {
    $product = Product::factory()->create();

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'CLEARANCE-001',
        'price' => '100.00',
        'cost_price' => '150.00', // Loss leader / clearance is allowed
    ]);

    $response->assertRedirect(route('admin.products.variants.index', $product));
});
