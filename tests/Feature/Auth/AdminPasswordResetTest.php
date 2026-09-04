<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('admin password reset email requested with generic safe response', function () {
    $admin = Admin::factory()->create(['email' => 'reset@test.com']);

    $response = $this->post(route('admin.password.email'), [
        'email' => 'reset@test.com',
    ]);

    $response->assertSessionHas('status');

    // Non-existent email receives same generic response to prevent account enumeration
    $fakeResponse = $this->post(route('admin.password.email'), [
        'email' => 'nonexistent@test.com',
    ]);
    $fakeResponse->assertSessionHas('status');
});

test('password reset completes successfully and increments auth_token_version', function () {
    $admin = Admin::factory()->create([
        'email' => 'resetme@test.com',
        'password' => Hash::make('OldPassword123!'),
        'auth_token_version' => 1,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    $response = $this->post(route('admin.password.update'), [
        'token' => $token,
        'email' => 'resetme@test.com',
        'password' => 'BrandNewPassword123!',
        'password_confirmation' => 'BrandNewPassword123!',
    ]);

    $response->assertRedirect(route('admin.login'))
        ->assertSessionHas('status');

    $admin->refresh();
    expect(Hash::check('BrandNewPassword123!', $admin->password))->toBeTrue()
        ->and($admin->auth_token_version)->toBe(2);
});
