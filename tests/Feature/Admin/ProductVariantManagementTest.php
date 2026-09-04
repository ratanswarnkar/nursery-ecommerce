<?php

use App\Enums\AttributeType;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
});

test('variant creation with options works correctly', function () {
    $product = Product::factory()->create();
    $attribute = Attribute::factory()->create(['type' => AttributeType::SELECT]);
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'TEST-SKU-VAR-1',
        'price' => '399.00',
        'compare_at_price' => '499.00',
        'cost_price' => '150.00',
        'attribute_value_ids' => [$value->id],
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('admin.products.variants.index', $product));

    $variant = ProductVariant::where('sku', 'TEST-SKU-VAR-1')->first();
    expect($variant)->not->toBeNull()
        ->and($variant->product_id)->toBe($product->id)
        ->and($variant->attributeValues)->toHaveCount(1)
        ->and($variant->attributeValues->first()->id)->toBe($value->id);
});

test('creating another variant with is_default=true atomically unsets previous default', function () {
    $product = Product::factory()->create();
    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'VAR-001',
        'is_default' => true,
    ]);

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'VAR-002',
        'price' => '499.00',
        'is_default' => '1',
    ]);

    $response->assertRedirect(route('admin.products.variants.index', $product));

    $variant2 = ProductVariant::where('sku', 'VAR-002')->first();
    expect($variant2->is_default)->toBeTrue()
        ->and($variant1->fresh()->is_default)->toBeFalse();
});

test('deleting the default variant automatically promotes another active variant', function () {
    $product = Product::factory()->create();
    $defaultVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'DEFAULT-VAR',
        'is_default' => true,
        'is_active' => true,
    ]);
    $otherVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'OTHER-VAR',
        'is_default' => false,
        'is_active' => true,
    ]);

    $response = $this->delete(route('admin.products.variants.destroy', [$product, $defaultVariant]));
    $response->assertRedirect(route('admin.products.variants.index', $product));

    expect(ProductVariant::find($defaultVariant->id))->toBeNull()
        ->and($otherVariant->fresh()->is_default)->toBeTrue();
});

test('deleting the only variant of a product is strictly rejected', function () {
    $product = Product::factory()->create();
    $onlyVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
    ]);

    $response = $this->delete(route('admin.products.variants.destroy', [$product, $onlyVariant]));
    $response->assertSessionHasErrors('variant');

    expect(ProductVariant::find($onlyVariant->id))->not->toBeNull();
});

test('deactivating default variant promotes another active variant', function () {
    $product = Product::factory()->create();
    $defaultVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'is_active' => true,
    ]);
    $activeVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => false,
        'is_active' => true,
    ]);

    $response = $this->post(route('admin.products.variants.toggle-status', [$product, $defaultVariant]));
    $response->assertRedirect();

    expect($defaultVariant->fresh()->is_active)->toBeFalse()
        ->and($defaultVariant->fresh()->is_default)->toBeFalse()
        ->and($activeVariant->fresh()->is_default)->toBeTrue();
});

test('deactivating default variant when no other active variant exists is rejected', function () {
    $product = Product::factory()->create();
    $defaultVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'is_active' => true,
    ]);
    // Other variant is already inactive
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => false,
        'is_active' => false,
    ]);

    $response = $this->post(route('admin.products.variants.toggle-status', [$product, $defaultVariant]));
    $response->assertSessionHasErrors('status');

    expect($defaultVariant->fresh()->is_active)->toBeTrue()
        ->and($defaultVariant->fresh()->is_default)->toBeTrue();
});

test('variant creation rejects prices with more than 2 decimal places or invalid compare_at', function () {
    $product = Product::factory()->create();

    // 3 decimal places
    $response3Dec = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'TEST-DEC-3',
        'price' => '100.999',
    ]);
    $response3Dec->assertSessionHasErrors('price');

    // compare_at lower than price
    $responseLowerCompare = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'TEST-COMPARE-LOWER',
        'price' => '500.00',
        'compare_at_price' => '450.00',
    ]);
    $responseLowerCompare->assertSessionHasErrors('compare_at_price');
});
