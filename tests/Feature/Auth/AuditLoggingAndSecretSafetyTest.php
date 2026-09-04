<?php

use App\Models\Admin;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;

test('audit logger scrubs sensitive keys like passwords, OTPs, secrets, and recovery codes', function () {
    $logger = new AuditLogger;

    $dirtyMetadata = [
        'email' => 'admin@test.com',
        'password' => 'SuperSecret123!',
        'otp' => '123456',
        'totp_code' => '654321',
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'recovery_code' => 'ABCD-1234',
        'safe_note' => 'Login attempt recorded',
    ];

    $scrubbed = $logger->scrubSensitiveData($dirtyMetadata);

    expect($scrubbed['email'])->toBe('admin@test.com')
        ->and($scrubbed['safe_note'])->toBe('Login attempt recorded')
        ->and($scrubbed['password'])->toBe('[REDACTED]')
        ->and($scrubbed['otp'])->toBe('[REDACTED]')
        ->and($scrubbed['totp_code'])->toBe('[REDACTED]')
        ->and($scrubbed['two_factor_secret'])->toBe('[REDACTED]')
        ->and($scrubbed['recovery_code'])->toBe('[REDACTED]');
});

test('audit log entries are created during authentication flow', function () {
    $admin = Admin::factory()->create();
    $logger = new AuditLogger;

    $log = $logger->logAdminEvent('admin.password.verified', $admin, ['action' => 'verified']);

    expect($log)->toBeInstanceOf(AuditLog::class)
        ->and($log->admin_id)->toBe($admin->id)
        ->and($log->action)->toBe('admin.password.verified');
});

test('security headers are present on HTTP responses', function () {
    $response = $this->get(route('admin.login'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});
