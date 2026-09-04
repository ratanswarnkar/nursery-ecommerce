<?php

namespace App\Services\Auth;

use App\Models\Admin;
use App\Models\AdminRecoveryCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RecoveryCodeService
{
    /**
     * Generate 8 cryptographically secure recovery codes for the admin,
     * persist their hashes to admin_recovery_codes, and return plain codes once.
     *
     * @return array<int, string> Plaintext recovery codes
     */
    public function generateForAdmin(Admin $admin, int $count = 8): array
    {
        return DB::transaction(function () use ($admin, $count) {
            // Invalidate any existing unused recovery codes for this admin
            AdminRecoveryCode::where('admin_id', $admin->id)
                ->whereNull('used_at')
                ->delete();

            $plainCodes = [];

            for ($i = 0; $i < $count; $i++) {
                $code = Str::upper(Str::random(8)).'-'.Str::upper(Str::random(8));
                $plainCodes[] = $code;

                AdminRecoveryCode::create([
                    'admin_id' => $admin->id,
                    'code_hash' => Hash::make($code),
                    'used_at' => null,
                ]);
            }

            return $plainCodes;
        });
    }

    /**
     * Atomically verify and consume a recovery code using database transaction and row locking.
     */
    public function consume(Admin $admin, string $inputCode): bool
    {
        $cleanInput = trim(strtoupper($inputCode));

        if (empty($cleanInput)) {
            return false;
        }

        return DB::transaction(function () use ($admin, $cleanInput) {
            // Lock unused recovery codes for this admin to prevent race-condition reuse
            $unusedCodes = AdminRecoveryCode::where('admin_id', $admin->id)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->get();

            foreach ($unusedCodes as $recoveryCode) {
                if (Hash::check($cleanInput, $recoveryCode->code_hash)) {
                    // Mark as consumed atomically
                    $recoveryCode->update([
                        'used_at' => now(),
                    ]);

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Count remaining active (unused) recovery codes for an admin.
     */
    public function countRemaining(Admin $admin): int
    {
        return AdminRecoveryCode::where('admin_id', $admin->id)
            ->whereNull('used_at')
            ->count();
    }
}
