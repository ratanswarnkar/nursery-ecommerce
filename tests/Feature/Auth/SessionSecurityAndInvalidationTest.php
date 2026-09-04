<?php

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

test('ValidateSessionVersion denies access when token version does not match database', function () {
    $admin = Admin::factory()->create(['auth_token_version' => 1]);

    // Admin is logged in with token version 1
    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => 1]);

    $response1 = $this->get(route('admin.dashboard'));
    $response1->assertOk();

    // Invalidate sessions by incrementing version in database (simulating another device logout-all or password change)
    $admin->increment('auth_token_version');

    // Next request from this browser must be rejected
    $response2 = $this->get(route('admin.dashboard'));
    $response2->assertRedirect(route('admin.login'));
    expect(Auth::guard('admin')->check())->toBeFalse();
});

test('logout-all increments auth_token_version and invalidates other sessions', function () {
    $admin = Admin::factory()->create(['auth_token_version' => 1]);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => 1]);

    $response = $this->post(route('admin.logout.all'));
    $response->assertRedirect(route('admin.login'));

    $admin->refresh();
    expect($admin->auth_token_version)->toBe(2)
        ->and(Auth::guard('admin')->check())->toBeFalse();
});

test('customer ValidateSessionVersion denies access when token version does not match', function () {
    $customer = Customer::factory()->create(['auth_token_version' => 1]);

    $this->actingAs($customer, 'customer');
    session(['customer_auth_token_version' => 1]);

    $response1 = $this->get(route('customer.home'));
    $response1->assertOk();

    $customer->increment('auth_token_version');

    $response2 = $this->get(route('customer.home'));
    $response2->assertRedirect(route('customer.login'));
    expect(Auth::guard('customer')->check())->toBeFalse();
});
