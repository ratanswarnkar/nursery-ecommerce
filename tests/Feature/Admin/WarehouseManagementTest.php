<?php

use App\Models\Admin;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
});

test('warehouse index lists registered warehouses', function () {
    Warehouse::create([
        'name' => 'North Greenhouse',
        'code' => 'WH-NORTH',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response = $this->get(route('admin.warehouses.index'));
    $response->assertOk();
    $response->assertSee('North Greenhouse');
    $response->assertSee('WH-NORTH');
});

test('first created warehouse automatically becomes default', function () {
    $response = $this->post(route('admin.warehouses.store'), [
        'name' => 'Central Hub',
        'code' => 'WH-CENTRAL',
        'is_active' => 1,
        'is_default' => 0, // Even if requested 0, first warehouse must become default
    ]);

    $response->assertRedirect(route('admin.warehouses.index'));

    $warehouse = Warehouse::where('code', 'WH-CENTRAL')->first();
    expect($warehouse)->not->toBeNull()
        ->and($warehouse->is_default)->toBeTrue();
});

test('creating subsequent warehouse as default demotes previous default', function () {
    $wh1 = Warehouse::create([
        'name' => 'First Hub',
        'code' => 'WH-FIRST',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response = $this->post(route('admin.warehouses.store'), [
        'name' => 'Second Hub',
        'code' => 'WH-SECOND',
        'is_active' => 1,
        'is_default' => 1,
    ]);

    $response->assertRedirect(route('admin.warehouses.index'));

    $wh1->refresh();
    $wh2 = Warehouse::where('code', 'WH-SECOND')->first();

    expect($wh1->is_default)->toBeFalse()
        ->and($wh2->is_default)->toBeTrue();
});

test('updating warehouse to default demotes previous default', function () {
    $wh1 = Warehouse::create([
        'name' => 'Hub 1',
        'code' => 'WH-1',
        'is_active' => true,
        'is_default' => true,
    ]);

    $wh2 = Warehouse::create([
        'name' => 'Hub 2',
        'code' => 'WH-2',
        'is_active' => true,
        'is_default' => false,
    ]);

    $response = $this->put(route('admin.warehouses.update', $wh2), [
        'name' => 'Hub 2 Updated',
        'code' => 'WH-2',
        'is_active' => 1,
        'is_default' => 1,
    ]);

    $response->assertRedirect(route('admin.warehouses.index'));

    $wh1->refresh();
    $wh2->refresh();

    expect($wh1->is_default)->toBeFalse()
        ->and($wh2->is_default)->toBeTrue();
});

test('deactivating default warehouse safely reassigns default to another active warehouse', function () {
    $wh1 = Warehouse::create([
        'name' => 'Active Default',
        'code' => 'WH-ACT-DEF',
        'is_active' => true,
        'is_default' => true,
    ]);

    $wh2 = Warehouse::create([
        'name' => 'Active Backup',
        'code' => 'WH-ACT-BAK',
        'is_active' => true,
        'is_default' => false,
    ]);

    // Deactivate wh1 via toggleStatus or update
    $response = $this->post(route('admin.warehouses.toggle-status', $wh1));
    $response->assertRedirect();

    $wh1->refresh();
    $wh2->refresh();

    expect($wh1->is_active)->toBeFalse()
        ->and($wh1->is_default)->toBeFalse()
        ->and($wh2->is_default)->toBeTrue();
});

test('cannot delete default warehouse', function () {
    $wh1 = Warehouse::create([
        'name' => 'Default Hub',
        'code' => 'WH-DEF',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response = $this->delete(route('admin.warehouses.destroy', $wh1));
    $response->assertRedirect(route('admin.warehouses.index'));
    $response->assertSessionHas('error');

    expect(Warehouse::find($wh1->id))->not->toBeNull();
});

test('cannot delete warehouse that has inventory records', function () {
    $wh1 = Warehouse::create([
        'name' => 'Hub 1',
        'code' => 'WH-1',
        'is_active' => true,
        'is_default' => true,
    ]);

    $wh2 = Warehouse::create([
        'name' => 'Hub 2',
        'code' => 'WH-2',
        'is_active' => true,
        'is_default' => false,
    ]);

    $variant = ProductVariant::factory()->create();

    Inventory::create([
        'warehouse_id' => $wh2->id,
        'product_variant_id' => $variant->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);

    $response = $this->delete(route('admin.warehouses.destroy', $wh2));
    $response->assertRedirect(route('admin.warehouses.index'));
    $response->assertSessionHas('error');

    expect(Warehouse::find($wh2->id))->not->toBeNull();
});

test('staff can view warehouses but cannot create, update or delete', function () {
    $staff = Admin::factory()->create();
    $staff->assignRole('Staff'); // Staff has warehouses.view, but not create/update/delete

    $this->actingAs($staff, 'admin');
    session(['admin_auth_token_version' => $staff->auth_token_version]);

    $response = $this->get(route('admin.warehouses.index'));
    $response->assertOk();

    $response = $this->post(route('admin.warehouses.store'), [
        'name' => 'Unauthorized Hub',
        'code' => 'WH-UNAUTH',
    ]);
    $response->assertForbidden();
});

test('admin without warehouse view permission cannot access warehouses', function () {
    $unprivileged = Admin::factory()->create(); // No roles or permissions assigned

    $this->actingAs($unprivileged, 'admin');
    session(['admin_auth_token_version' => $unprivileged->auth_token_version]);

    $response = $this->get(route('admin.warehouses.index'));
    $response->assertForbidden();
});
