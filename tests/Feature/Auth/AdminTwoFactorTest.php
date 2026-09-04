<?php

use App\Models\Admin;
use App\Models\AdminRecoveryCode;
use App\Services\Auth\RecoveryCodeService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    RateLimiter::clear('admin-2fa');
});

test('first-time admin without 2FA is forced to enrollment route', function () {
    $admin = Admin::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->post(route('admin.login.submit'), [
        'email' => $admin->email,
        'password' => 'adminpassword123',
    ]);

    $response->assertRedirect(route('admin.2fa.enroll'));
});

test('temporary enrollment secret is ephemeral and abandoned enrollment leaves no usable secret', function () {
    $admin = Admin::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $this->withSession([
        'admin_2fa_pending' => [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])->get(route('admin.2fa.enroll'));

    // Secret exists in session
    expect(session()->has('admin_2fa_enrollment'))->toBeTrue();

    // But database model must still have NULL secret
    $admin->refresh();
    expect($admin->two_factor_secret)->toBeNull()
        ->and($admin->two_factor_confirmed_at)->toBeNull();
});

test('confirming 2FA enrollment persists secret, creates 8 hashed recovery codes, and authenticates admin', function () {
    $admin = Admin::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $twoFactorService = new TwoFactorService;
    $secret = $twoFactorService->generateSecretKey();

    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp($secret);

    $response = $this->withSession([
        'admin_2fa_pending' => [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
        'admin_2fa_enrollment' => [
            'secret' => encrypt($secret),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])->post(route('admin.2fa.enroll.confirm'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('admin.dashboard'));

    // Admin is now fully authenticated
    expect(Auth::guard('admin')->check())->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($admin->id);

    // Database is updated
    $admin->refresh();
    expect($admin->two_factor_confirmed_at)->not->toBeNull()
        ->and($admin->two_factor_secret)->not->toBeNull();

    // 8 recovery codes created in dedicated table
    $recoveryCodes = AdminRecoveryCode::where('admin_id', $admin->id)->get();
    expect($recoveryCodes)->toHaveCount(8);
});

test('valid TOTP code on subsequent login authenticates admin guard', function () {
    $twoFactorService = new TwoFactorService;
    $secret = $twoFactorService->generateSecretKey();

    $admin = Admin::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp($secret);

    $response = $this->withSession([
        'admin_2fa_pending' => [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])->post(route('admin.2fa.verify'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(Auth::guard('admin')->check())->toBeTrue();
});

test('invalid TOTP code fails and is rate-limited', function () {
    $twoFactorService = new TwoFactorService;
    $secret = $twoFactorService->generateSecretKey();

    $admin = Admin::factory()->create([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $session = [
        'admin_2fa_pending' => [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ];

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $response = $this->withSession($session)->post(route('admin.2fa.verify'), [
            'code' => '000000',
        ]);
        $response->assertSessionHasErrors('code');
    }

    // 6th attempt must be rate-limited (HTTP 429)
    $rateLimitedResponse = $this->withSession($session)->post(route('admin.2fa.verify'), [
        'code' => '000000',
    ]);
    $rateLimitedResponse->assertStatus(429);
});

test('valid recovery code completes login, marks used_at, and cannot be reused', function () {
    $admin = Admin::factory()->create([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
    ]);

    $recoveryService = new RecoveryCodeService;
    $codes = $recoveryService->generateForAdmin($admin, 8);
    $recoveryCode = $codes[0];

    $response = $this->withSession([
        'admin_2fa_pending' => [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])->post(route('admin.2fa.verify'), [
        'recovery_code' => $recoveryCode,
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(Auth::guard('admin')->check())->toBeTrue();

    // Code is marked as used
    $usedRecord = AdminRecoveryCode::where('admin_id', $admin->id)->whereNotNull('used_at')->first();
    expect($usedRecord)->not->toBeNull();

    // Log out admin
    Auth::guard('admin')->logout();

    // Attempting to use the same recovery code again must fail
    $replayResponse = $this->withSession([
        'admin_2fa_pending' => [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ],
    ])->post(route('admin.2fa.verify'), [
        'recovery_code' => $recoveryCode,
    ]);

    $replayResponse->assertSessionHasErrors('recovery_code');
    expect(Auth::guard('admin')->check())->toBeFalse();
});
