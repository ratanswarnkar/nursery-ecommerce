<?php

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
});

test('admin dashboard requires authentication', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('admin.login'));
});

test('admin dashboard displays accurate catalog summary counts', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole('Super Admin');

    $brands = Brand::factory()->count(2)->create();
    Category::factory()->count(4)->create();

    $activeProducts = Product::factory()->count(3)->create([
        'brand_id' => $brands->first()->id,
        'is_active' => true,
    ]);
    Product::factory()->create([
        'brand_id' => $brands->last()->id,
        'is_active' => false,
    ]);

    // Create low stock inventory on an existing product's variant
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $activeProducts->first()->id,
    ]);
    Inventory::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 2,
        'safety_stock' => 5, // quantity <= safety_stock
    ]);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
    $response->assertViewHas('totalProducts', 4);
    $response->assertViewHas('activeProducts', 3);
    $response->assertViewHas('totalCategories', 4);
    $response->assertViewHas('totalBrands', 2);
    $response->assertViewHas('lowStockCount', 1);
});

test('roles with dashboard.view can access dashboard', function () {
    foreach (['Super Admin', 'Admin', 'Manager', 'Staff'] as $roleName) {
        $admin = Admin::factory()->create();
        $admin->assignRole($roleName);

        $this->actingAs($admin, 'admin');
        session(['admin_auth_token_version' => $admin->auth_token_version]);

        $this->get(route('admin.dashboard'))->assertOk();
    }
});
