<?php

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
});

test('Super Admin has full access to all catalog endpoints', function () {
    $superAdmin = Admin::factory()->create();
    $superAdmin->assignRole('Super Admin');

    $this->actingAs($superAdmin, 'admin');
    session(['admin_auth_token_version' => $superAdmin->auth_token_version]);

    $this->get(route('admin.dashboard'))->assertOk();
    $this->get(route('admin.products.index'))->assertOk();
    $this->get(route('admin.products.create'))->assertOk();
    $this->get(route('admin.categories.index'))->assertOk();
    $this->get(route('admin.brands.index'))->assertOk();
    $this->get(route('admin.attributes.index'))->assertOk();
});

test('Admin role has operational access to catalog mutations', function () {
    $admin = Admin::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $this->get(route('admin.products.create'))->assertOk();
    $this->get(route('admin.categories.create'))->assertOk();
    $this->get(route('admin.brands.create'))->assertOk();
    $this->get(route('admin.attributes.create'))->assertOk();
});

test('Manager role can view catalog but is forbidden from creating products or categories', function () {
    $manager = Admin::factory()->create();
    $manager->assignRole('Manager');

    $this->actingAs($manager, 'admin');
    session(['admin_auth_token_version' => $manager->auth_token_version]);

    // Allowed to view
    $this->get(route('admin.products.index'))->assertOk();
    $this->get(route('admin.categories.index'))->assertOk();
    $this->get(route('admin.brands.index'))->assertOk();
    $this->get(route('admin.attributes.index'))->assertOk();

    // Forbidden from create
    $this->get(route('admin.products.create'))->assertForbidden();
    $this->get(route('admin.categories.create'))->assertForbidden();
    $this->get(route('admin.brands.create'))->assertForbidden();
    $this->get(route('admin.attributes.create'))->assertForbidden();
});

test('Staff role can view catalog but is forbidden from modifying categories', function () {
    $staff = Admin::factory()->create();
    $staff->assignRole('Staff');

    $this->actingAs($staff, 'admin');
    session(['admin_auth_token_version' => $staff->auth_token_version]);

    // View allowed
    $this->get(route('admin.products.index'))->assertOk();
    $this->get(route('admin.categories.index'))->assertOk();

    // Mutation forbidden
    $this->post(route('admin.categories.store'), ['name' => 'Staff Category'])->assertForbidden();
});

test('Manager and Staff cannot perform destructive delete operations', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create();

    // Manager
    $manager = Admin::factory()->create();
    $manager->assignRole('Manager');
    $this->actingAs($manager, 'admin');
    session(['admin_auth_token_version' => $manager->auth_token_version]);

    $this->delete(route('admin.products.destroy', $product))->assertForbidden();
    $this->delete(route('admin.categories.destroy', $category))->assertForbidden();
    $this->delete(route('admin.brands.destroy', $brand))->assertForbidden();

    // Staff
    $staff = Admin::factory()->create();
    $staff->assignRole('Staff');
    $this->actingAs($staff, 'admin');
    session(['admin_auth_token_version' => $staff->auth_token_version]);

    $this->delete(route('admin.products.destroy', $product))->assertForbidden();
    $this->delete(route('admin.categories.destroy', $category))->assertForbidden();
    $this->delete(route('admin.brands.destroy', $brand))->assertForbidden();
});

test('unauthenticated customer cannot access admin catalog routes', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer');
    session(['customer_auth_token_version' => $customer->auth_token_version]);

    $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});
