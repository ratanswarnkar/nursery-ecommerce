<?php

use App\Models\Admin;
use App\Models\Customer;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
});

test('Super Admin role has full administrative access to all protected permission routes', function () {
    $superAdmin = Admin::factory()->create();
    $superAdmin->assignRole('Super Admin');

    $this->actingAs($superAdmin, 'admin');
    session(['admin_auth_token_version' => $superAdmin->auth_token_version]);

    $this->get(route('admin.dashboard'))->assertOk();
    $this->get('/admin/test/orders-view')->assertOk();
    $this->get('/admin/test/roles-manage')->assertOk();
});

test('Admin role has operational permissions but is forbidden from roles management', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    // Allowed
    $this->get('/admin/test/orders-view')->assertOk();

    // Forbidden from roles.manage
    $this->get('/admin/test/roles-manage')->assertForbidden();
});

test('Staff role has view permissions and is forbidden from roles management', function () {
    $staff = Admin::factory()->create();
    $staff->assignRole('Staff');

    $this->actingAs($staff, 'admin');
    session(['admin_auth_token_version' => $staff->auth_token_version]);

    // Allowed
    $this->get('/admin/test/orders-view')->assertOk();

    // Forbidden from roles.manage
    $this->get('/admin/test/roles-manage')->assertForbidden();
});

test('unauthenticated customer cannot access admin routes', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer');
    session(['customer_auth_token_version' => $customer->auth_token_version]);

    // Attempting to access admin route must redirect to admin.login
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('admin.login'));
});

test('admin cannot access customer route without authenticating as customer', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    // Attempting to access customer route must redirect to customer.login
    $response = $this->get(route('customer.home'));
    $response->assertRedirect(route('customer.login'));
});
