<?php

use App\Models\Admin;
use App\Models\AdminLoginActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('admin-login');
});

test('admin login with valid credentials creates pending 2FA state without authenticating admin guard', function () {
    $admin = Admin::factory()->create([
        'email' => 'admin@test.com',
        'password' => Hash::make('SecretPass123!'),
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'email' => 'admin@test.com',
        'password' => 'SecretPass123!',
    ]);

    $response->assertRedirect(route('admin.2fa.challenge'));

    // Admin guard must NOT be authenticated yet
    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(session()->has('admin_2fa_pending'))->toBeTrue()
        ->and(session('admin_2fa_pending.admin_id'))->toBe($admin->id);

    // Login activity recorded
    $activity = AdminLoginActivity::where('admin_id', $admin->id)->latest('id')->first();
    expect($activity->is_successful)->toBeTrue();
});

test('invalid password fails and records failed login activity', function () {
    $admin = Admin::factory()->create([
        'email' => 'admin@test.com',
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'email' => 'admin@test.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(session()->has('admin_2fa_pending'))->toBeFalse();

    $activity = AdminLoginActivity::where('admin_id', $admin->id)->latest('id')->first();
    expect($activity->is_successful)->toBeFalse()
        ->and($activity->failure_reason)->toBe('Invalid password');
});

test('inactive admin cannot log in', function () {
    $admin = Admin::factory()->create([
        'email' => 'inactive@test.com',
        'password' => Hash::make('SecretPass123!'),
        'is_active' => false,
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'email' => 'inactive@test.com',
        'password' => 'SecretPass123!',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Auth::guard('admin')->check())->toBeFalse();

    $activity = AdminLoginActivity::where('admin_id', $admin->id)->latest('id')->first();
    expect($activity->is_successful)->toBeFalse()
        ->and($activity->failure_reason)->toBe('Account inactive');
});

test('pending 2FA session alone cannot access protected admin routes', function () {
    $admin = Admin::factory()->create();

    // Set pending 2FA state in session
    session(['admin_2fa_pending' => [
        'admin_id' => $admin->id,
        'auth_token_version' => $admin->auth_token_version,
        'expires_at' => now()->addMinutes(10)->timestamp,
    ]]);

    // Protected dashboard route must reject with redirect to login
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('admin.login'));
});

test('admin logout terminates admin session', function () {
    $admin = Admin::factory()->create();
    Auth::guard('admin')->login($admin);
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    expect(Auth::guard('admin')->check())->toBeTrue();

    $response = $this->post(route('admin.logout'));
    $response->assertRedirect(route('admin.login'));

    expect(Auth::guard('admin')->check())->toBeFalse();
});
