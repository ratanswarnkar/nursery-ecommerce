<?php

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxClass;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
});

test('product index lists products with filters and search', function () {
    $brand1 = Brand::factory()->create(['name' => 'GreenHaven']);
    $brand2 = Brand::factory()->create(['name' => 'FloraBotanica']);

    Product::factory()->create(['name' => 'Monstera Deliciosa', 'base_sku' => 'MONST-001', 'brand_id' => $brand1->id]);
    Product::factory()->create(['name' => 'Snake Plant', 'base_sku' => 'SNK-002', 'brand_id' => $brand2->id]);

    // Search by name
    $response = $this->get(route('admin.products.index', ['search' => 'Monstera']));
    $response->assertOk();
    $response->assertSee('Monstera Deliciosa');
    $response->assertDontSee('Snake Plant');

    // Search by SKU
    $responseSku = $this->get(route('admin.products.index', ['search' => 'SNK-002']));
    $responseSku->assertOk();
    $responseSku->assertSee('Snake Plant');
    $responseSku->assertDontSee('Monstera Deliciosa');
});

test('product creation generates default variant, primary category mapping, and SEO', function () {
    $brand = Brand::factory()->create();
    $taxClass = TaxClass::factory()->create();
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();

    $response = $this->post(route('admin.products.store'), [
        'name' => 'ZZ Plant (Zamioculcas zamiifolia)',
        'base_sku' => 'ZZ-PLT-001',
        'brand_id' => $brand->id,
        'tax_class_id' => $taxClass->id,
        'short_description' => 'Indestructible low-light indoor plant',
        'full_description' => 'Thrives on neglect and minimal watering.',
        'category_ids' => [$cat1->id, $cat2->id],
        'primary_category_id' => $cat1->id,
        'initial_price' => '449.00',
        'initial_compare_at_price' => '599.00',
        'initial_cost_price' => '200.00',
        'is_active' => '1',
        'is_featured' => '1',
        'meta_title' => 'Buy ZZ Plant Online',
        'meta_description' => 'Best ZZ plant for low light home and office decor.',
    ]);

    $product = Product::where('base_sku', 'ZZ-PLT-001')->first();
    expect($product)->not->toBeNull();

    $response->assertRedirect(route('admin.products.show', $product));

    // Verify categories & primary flag
    expect($product->categories)->toHaveCount(2);
    $primaryPivot = $product->categories->firstWhere('id', $cat1->id)->pivot;
    $secondaryPivot = $product->categories->firstWhere('id', $cat2->id)->pivot;
    expect((bool) $primaryPivot->is_primary)->toBeTrue()
        ->and((bool) $secondaryPivot->is_primary)->toBeFalse();

    // Verify initial default variant
    expect($product->variants)->toHaveCount(1);
    $defaultVariant = $product->variants->first();
    expect($defaultVariant->sku)->toBe('ZZ-PLT-001')
        ->and((float) $defaultVariant->price)->toBe(449.00)
        ->and((float) $defaultVariant->compare_at_price)->toBe(599.00)
        ->and($defaultVariant->is_default)->toBeTrue();

    // Verify SEO
    expect($product->seoMetadata)->not->toBeNull()
        ->and($product->seoMetadata->meta_title)->toBe('Buy ZZ Plant Online');
});

test('primary_category_id must be among selected category_ids', function () {
    $cat1 = Category::factory()->create();
    $unselectedCat = Category::factory()->create();

    $response = $this->post(route('admin.products.store'), [
        'name' => 'Aloe Vera',
        'base_sku' => 'ALO-001',
        'category_ids' => [$cat1->id],
        'primary_category_id' => $unselectedCat->id, // invalid
        'initial_price' => '199.00',
    ]);

    $response->assertSessionHasErrors('primary_category_id');
});

test('product with order references is soft-deleted, not hard-deleted', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
    ]);

    $response = $this->delete(route('admin.products.destroy', $product));
    $response->assertRedirect(route('admin.products.index'));

    // Verify soft delete: row still exists in DB but with deleted_at set
    expect(Product::withTrashed()->find($product->id))->not->toBeNull()
        ->and(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id)->is_active)->toBeFalse();
});
