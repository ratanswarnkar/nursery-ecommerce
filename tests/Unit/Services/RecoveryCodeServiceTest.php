<?php

use App\Models\Admin;
use App\Models\AdminRecoveryCode;
use App\Services\Auth\RecoveryCodeService;
use Illuminate\Support\Facades\Hash;

test('generates 8 recovery codes stored hashed in admin_recovery_codes table', function () {
    $admin = Admin::factory()->create();
    $service = new RecoveryCodeService;

    $codes = $service->generateForAdmin($admin, 8);

    expect($codes)->toBeArray()->toHaveCount(8);

    $dbRecords = AdminRecoveryCode::where('admin_id', $admin->id)->get();
    expect($dbRecords)->toHaveCount(8);

    // Verify plaintext is not stored in DB, but hashes match
    foreach ($codes as $index => $plainCode) {
        expect($dbRecords[$index]->code_hash)->not->toBe($plainCode)
            ->and(Hash::check($plainCode, $dbRecords[$index]->code_hash))->toBeTrue()
            ->and($dbRecords[$index]->used_at)->toBeNull();
    }
});

test('consume atomically marks recovery code used and prevents reuse', function () {
    $admin = Admin::factory()->create();
    $service = new RecoveryCodeService;

    $codes = $service->generateForAdmin($admin, 8);
    $firstCode = $codes[0];

    expect($service->countRemaining($admin))->toBe(8);

    // First use must succeed
    $consumed = $service->consume($admin, $firstCode);
    expect($consumed)->toBeTrue()
        ->and($service->countRemaining($admin))->toBe(7);

    // Replay attack with same recovery code must fail
    $reused = $service->consume($admin, $firstCode);
    expect($reused)->toBeFalse();
});

test('invalid recovery code returns false without affecting unused codes', function () {
    $admin = Admin::factory()->create();
    $service = new RecoveryCodeService;

    $service->generateForAdmin($admin, 8);

    $consumed = $service->consume($admin, 'INVALID-CODE-1234');
    expect($consumed)->toBeFalse()
        ->and($service->countRemaining($admin))->toBe(8);
});
