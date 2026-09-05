<?php

use App\Enums\StockMovementType;
use App\Models\Admin;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $this->warehouse = Warehouse::create([
        'name' => 'Main Greenhouse',
        'code' => 'GH-MAIN',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->variant = ProductVariant::factory()->create();
});

test('inventory index lists variant stock across warehouses', function () {
    Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reserved_quantity' => 5,
        'safety_stock' => 10,
    ]);

    $response = $this->get(route('admin.inventory.index'));
    $response->assertOk();
    $response->assertSee($this->variant->sku);
    $response->assertSee('GH-MAIN');
    $response->assertSee('50'); // total
    $response->assertSee('45'); // available
});

test('inventory can be filtered by warehouse and low stock threshold', function () {
    $variant2 = ProductVariant::factory()->create();

    // Healthy item
    Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
        'safety_stock' => 10,
    ]);

    // Low stock item
    Inventory::create([
        'product_variant_id' => $variant2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 4,
        'reserved_quantity' => 0,
        'safety_stock' => 5,
    ]);

    $response = $this->get(route('admin.inventory.index', ['low_stock' => 1]));
    $response->assertOk();
    $response->assertSee($variant2->sku);
    $response->assertDontSee($this->variant->sku);
});

test('stock adjustment positive delta increases quantity and writes stock movement', function () {
    $inv = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'safety_stock' => 5,
    ]);

    $response = $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => 15,
        'type' => 'inbound',
        'notes' => 'Shipment from grower #PO-4001',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $inv->refresh();
    expect($inv->quantity)->toBe(25)
        ->and($inv->available_quantity)->toBe(25);

    $movement = StockMovement::where('product_variant_id', $this->variant->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(15)
        ->and($movement->previous_quantity)->toBe(10)
        ->and($movement->new_quantity)->toBe(25)
        ->and($movement->type->value ?? $movement->type)->toBe('inbound')
        ->and($movement->notes)->toBe('Shipment from grower #PO-4001');
});

test('stock adjustment negative delta decreases quantity and records audit', function () {
    $inv = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 5,
    ]);

    $response = $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => -6,
        'type' => 'outbound',
        'notes' => 'Pest damage identified during morning inspection',
    ]);

    $response->assertRedirect();
    $inv->refresh();

    expect($inv->quantity)->toBe(14);

    $movement = StockMovement::where('product_variant_id', $this->variant->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(-6)
        ->and($movement->previous_quantity)->toBe(20)
        ->and($movement->new_quantity)->toBe(14)
        ->and($movement->type->value ?? $movement->type)->toBe('outbound');
});

test('stock adjustment cannot reduce stock below reserved quantity or negative', function () {
    $inv = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 6,
        'safety_stock' => 2,
    ]);

    // Attempting to deduct 5 leaves 5 < 6 reserved -> invalid
    $response = $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => -5,
        'type' => 'adjustment',
    ]);

    $response->assertSessionHasErrors('quantity');

    $inv->refresh();
    expect($inv->quantity)->toBe(10); // Unchanged

    // Attempting to deduct more than total stock -> invalid
    $response2 = $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => -15,
        'type' => 'adjustment',
    ]);

    $response2->assertSessionHasErrors('quantity');
    expect($inv->fresh()->quantity)->toBe(10);
});

test('stock adjustment set mode updates quantity to exact count', function () {
    $inv = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 2,
        'safety_stock' => 5,
    ]);

    $response = $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'set',
        'quantity' => 30,
        'notes' => 'Cycle count reconciled',
    ]);

    $response->assertRedirect();
    $inv->refresh();

    expect($inv->quantity)->toBe(30);

    $movement = StockMovement::where('product_variant_id', $this->variant->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(20) // Delta of 30 - 10
        ->and($movement->previous_quantity)->toBe(10)
        ->and($movement->new_quantity)->toBe(30);
});

test('movements ledger displays history and is viewable', function () {
    StockMovement::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'type' => StockMovementType::INBOUND,
        'quantity' => 50,
        'previous_quantity' => 0,
        'new_quantity' => 50,
        'notes' => 'Initial nursery intake',
    ]);

    $response = $this->get(route('admin.inventory.movements'));
    $response->assertOk();
    $response->assertSee('Initial nursery intake');
    $response->assertSee('+50');
});

test('inventory rbac: super admin, admin, and manager can adjust inventory, staff is view-only', function () {
    // 1. Super Admin
    $superAdmin = Admin::factory()->create();
    $superAdmin->assignRole('Super Admin');
    $this->actingAs($superAdmin, 'admin');
    session(['admin_auth_token_version' => $superAdmin->auth_token_version]);

    $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => 10,
        'type' => 'inbound',
    ])->assertRedirect()->assertSessionHas('success');

    // 2. Admin
    $adminUser = Admin::factory()->create();
    $adminUser->assignRole('Admin');
    $this->actingAs($adminUser, 'admin');
    session(['admin_auth_token_version' => $adminUser->auth_token_version]);

    $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => 5,
        'type' => 'inbound',
    ])->assertRedirect()->assertSessionHas('success');

    // 3. Manager
    $manager = Admin::factory()->create();
    $manager->assignRole('Manager');
    $this->actingAs($manager, 'admin');
    session(['admin_auth_token_version' => $manager->auth_token_version]);

    $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => 5,
        'type' => 'inbound',
    ])->assertRedirect()->assertSessionHas('success');

    // 4. Staff (View only, no inventory.manage)
    $staff = Admin::factory()->create();
    $staff->assignRole('Staff');
    $this->actingAs($staff, 'admin');
    session(['admin_auth_token_version' => $staff->auth_token_version]);

    $this->get(route('admin.inventory.index'))->assertOk();

    $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => 5,
        'type' => 'inbound',
    ])->assertForbidden();

    // 5. Unassigned Admin (No permissions)
    $unassigned = Admin::factory()->create();
    $this->actingAs($unassigned, 'admin');
    session(['admin_auth_token_version' => $unassigned->auth_token_version]);

    $this->get(route('admin.inventory.index'))->assertForbidden();
    $this->post(route('admin.inventory.adjust'), [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'adjustment_mode' => 'delta',
        'quantity' => 5,
        'type' => 'inbound',
    ])->assertForbidden();
});

test('database check constraint genuinely prevents persisting impossible inventory states', function () {
    $v2 = ProductVariant::factory()->create();

    // 1. Invariant: quantity >= reserved_quantity. Attempt quantity = 5, reserved_quantity = 10
    expect(function () use ($v2) {
        Inventory::create([
            'product_variant_id' => $v2->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'reserved_quantity' => 10,
            'safety_stock' => 0,
        ]);
    })->toThrow(QueryException::class);

    // 2. Invariant: quantity >= 0. Attempt negative quantity
    expect(function () use ($v2) {
        Inventory::create([
            'product_variant_id' => $v2->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => -5,
            'reserved_quantity' => 0,
            'safety_stock' => 0,
        ]);
    })->toThrow(QueryException::class);

    // 3. Invariant: reserved_quantity >= 0. Attempt negative reserved_quantity
    expect(function () use ($v2) {
        Inventory::create([
            'product_variant_id' => $v2->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => -2,
            'safety_stock' => 0,
        ]);
    })->toThrow(QueryException::class);

    // 4. Valid state succeeds: quantity = 10, reserved_quantity = 3 (10 >= 3 >= 0)
    $validInventory = Inventory::create([
        'product_variant_id' => $v2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 3,
        'safety_stock' => 0,
    ]);

    expect($validInventory)->not->toBeNull()
        ->and($validInventory->available_quantity)->toBe(7);
});
