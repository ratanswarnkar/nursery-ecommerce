<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
});

test('authenticated super admin sees all 9 sidebar navigation items', function () {
    $admin = Admin::factory()->create(['is_active' => true]);
    $admin->assignRole('Super Admin');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();

    // Verify all 9 routes are present in the rendered sidebar HTML
    $response->assertSee(route('admin.dashboard'));
    $response->assertSee(route('admin.products.index'));
    $response->assertSee(route('admin.categories.index'));
    $response->assertSee(route('admin.brands.index'));
    $response->assertSee(route('admin.attributes.index'));
    $response->assertSee(route('admin.inventory.index'));
    $response->assertSee(route('admin.inventory.movements'));
    $response->assertSee(route('admin.warehouses.index'));
    $response->assertSee(route('admin.orders.index'));

    // Verify item text
    $response->assertSee('Dashboard');
    $response->assertSee('Products');
    $response->assertSee('Categories');
    $response->assertSee('Brands');
    $response->assertSee('Attributes');
    $response->assertSee('Inventory');
    $response->assertSee('Stock Movements');
    $response->assertSee('Warehouses');
    $response->assertSee('Orders');
});

test('every linked sidebar route resolves with HTTP 200 without broken routes or 404s', function () {
    $admin = Admin::factory()->create(['is_active' => true]);
    $admin->assignRole('Super Admin');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $routes = [
        'admin.dashboard',
        'admin.products.index',
        'admin.categories.index',
        'admin.brands.index',
        'admin.attributes.index',
        'admin.inventory.index',
        'admin.inventory.movements',
        'admin.warehouses.index',
        'admin.orders.index',
    ];

    foreach ($routes as $routeName) {
        $response = $this->get(route($routeName));
        $response->assertOk();
    }
});

test('active menu highlighting applies accurately to current route without overlapping', function () {
    $admin = Admin::factory()->create(['is_active' => true]);
    $admin->assignRole('Super Admin');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    // On Inventory Index
    $invResponse = $this->get(route('admin.inventory.index'));
    $invResponse->assertOk();
    $invContent = $invResponse->getContent();

    // Verify inventory link has active class
    expect($invContent)->toContain('href="'.route('admin.inventory.index').'" class="nav-item active"');
    // Verify movements link does NOT have active class
    expect($invContent)->toContain('href="'.route('admin.inventory.movements').'" class="nav-item "');

    // On Inventory Movements
    $movResponse = $this->get(route('admin.inventory.movements'));
    $movResponse->assertOk();
    $movContent = $movResponse->getContent();

    // Verify movements link has active class
    expect($movContent)->toContain('href="'.route('admin.inventory.movements').'" class="nav-item active"');
    // Verify inventory link does NOT have active class
    expect($movContent)->toContain('href="'.route('admin.inventory.index').'" class="nav-item "');
});

test('unauthorized admin cannot see or access restricted sidebar links', function () {
    // Create custom role with only dashboard and products view
    $customRole = Role::create(['name' => 'CatalogViewer', 'guard_name' => 'admin']);
    $customRole->givePermissionTo(['dashboard.view', 'products.view']);

    $admin = Admin::factory()->create(['is_active' => true]);
    $admin->assignRole($customRole);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();

    // Permitted links are visible
    $response->assertSee(route('admin.dashboard'));
    $response->assertSee(route('admin.products.index'));

    // Restricted links are NOT rendered in sidebar
    $response->assertDontSee(route('admin.categories.index'));
    $response->assertDontSee(route('admin.brands.index'));
    $response->assertDontSee(route('admin.attributes.index'));
    $response->assertDontSee(route('admin.inventory.index'));
    $response->assertDontSee(route('admin.inventory.movements'));
    $response->assertDontSee(route('admin.warehouses.index'));
    $response->assertDontSee(route('admin.orders.index'));

    // Attempting direct access to restricted routes is forbidden (403)
    $this->get(route('admin.orders.index'))->assertForbidden();
    $this->get(route('admin.inventory.index'))->assertForbidden();
    $this->get(route('admin.inventory.movements'))->assertForbidden();
    $this->get(route('admin.warehouses.index'))->assertForbidden();
});

test('order listing page renders direct invoice link for paid eligible orders', function () {
    $admin = Admin::factory()->create(['is_active' => true]);
    $admin->assignRole('Super Admin');

    $order = Order::factory()->create([
        'status' => OrderStatus::CONFIRMED,
        'payment_status' => PaymentStatus::PAID,
    ]);

    PaymentTransaction::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PAID,
        'amount' => $order->grand_total,
    ]);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.orders.index'));
    $response->assertOk();
    $response->assertSee(route('admin.orders.invoice', $order));
});
