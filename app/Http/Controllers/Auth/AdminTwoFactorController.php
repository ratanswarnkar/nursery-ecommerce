<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminTwoFactorEnrollRequest;
use App\Http\Requests\Auth\AdminTwoFactorVerifyRequest;
use App\Models\Admin;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\RecoveryCodeService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminTwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private RecoveryCodeService $recoveryCodeService,
        private AuditLogger $auditLogger
    ) {}

    public function showChallenge(Request $request): View|RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('pending_admin');

        if ($admin->two_factor_confirmed_at === null || empty($admin->two_factor_secret)) {
            return redirect()->route('admin.2fa.enroll');
        }

        return view('auth.admin.two-factor-challenge');
    }

    public function verify(AdminTwoFactorVerifyRequest $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('pending_admin');

        // Verification Option 1: 6-digit TOTP code
        if ($request->filled('code')) {
            $isValid = $this->twoFactorService->verifyKey(
                (string) $admin->two_factor_secret,
                $request->code
            );

            if (! $isValid) {
                $this->auditLogger->logAdminEvent('admin.2fa.failure', $admin, [
                    'method' => 'totp',
                ]);

                throw ValidationException::withMessages([
                    'code' => ['The provided two-factor authentication code is invalid.'],
                ]);
            }

            $this->auditLogger->logAdminEvent('admin.2fa.success', $admin, [
                'method' => 'totp',
            ]);
        }

        // Verification Option 2: Single-use recovery code
        if ($request->filled('recovery_code')) {
            $isConsumed = $this->recoveryCodeService->consume($admin, $request->recovery_code);

            if (! $isConsumed) {
                $this->auditLogger->logAdminEvent('admin.recovery_code.failed', $admin);

                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided recovery code is invalid or has already been used.'],
                ]);
            }

            $this->auditLogger->logAdminEvent('admin.recovery_code.used', $admin, [
                'remaining_codes' => $this->recoveryCodeService->countRemaining($admin),
            ]);
        }

        // 2FA completed: promote to fully authenticated admin session
        $request->session()->forget('admin_2fa_pending');
        Auth::guard('admin')->login($admin);

        $request->session()->regenerate();
        $request->session()->put('admin_auth_token_version', $admin->auth_token_version);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showEnroll(Request $request): View|RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('pending_admin');

        if ($admin->two_factor_confirmed_at !== null && ! empty($admin->two_factor_secret)) {
            return redirect()->route('admin.2fa.challenge');
        }

        // Ephemeral enrollment lifecycle: retrieve or generate temporary unconfirmed secret
        $enrollment = $request->session()->get('admin_2fa_enrollment');

        if (! $enrollment || now()->timestamp > ($enrollment['expires_at'] ?? 0)) {
            $secret = $this->twoFactorService->generateSecretKey();
            $request->session()->put('admin_2fa_enrollment', [
                'secret' => encrypt($secret),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);
        } else {
            $secret = decrypt($enrollment['secret']);
        }

        $companyName = (string) config('app.name', 'Nursery E-Commerce');
        $qrSvg = $this->twoFactorService->getQrCodeSvg($companyName, $admin->email, $secret);

        return view('auth.admin.two-factor-enroll', [
            'secret' => $secret,
            'qrSvg' => $qrSvg,
            'admin' => $admin,
        ]);
    }

    public function confirmEnroll(AdminTwoFactorEnrollRequest $request): RedirectResponse|View
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('pending_admin');

        $enrollment = $request->session()->get('admin_2fa_enrollment');

        if (! $enrollment || now()->timestamp > ($enrollment['expires_at'] ?? 0)) {
            return redirect()->route('admin.2fa.enroll')->withErrors([
                'code' => ['Two-factor enrollment session has expired. Please try again.'],
            ]);
        }

        $secret = decrypt($enrollment['secret']);

        // Verify confirmation code against pending secret
        if (! $this->twoFactorService->verifyKey($secret, $request->code)) {
            $this->auditLogger->logAdminEvent('admin.2fa.enroll_failed', $admin);

            throw ValidationException::withMessages([
                'code' => ['The verification code is incorrect. Ensure device clock is synced.'],
            ]);
        }

        // Ephemeral secret confirmed: persist permanently and generate recovery codes atomically
        $plainRecoveryCodes = DB::transaction(function () use ($admin, $secret) {
            $admin->two_factor_secret = $secret;
            $admin->two_factor_confirmed_at = now();
            $admin->save();

            return $this->recoveryCodeService->generateForAdmin($admin, 8);
        });

        // Clear enrollment and pending states
        $request->session()->forget('admin_2fa_enrollment');
        $request->session()->forget('admin_2fa_pending');

        // Log admin in
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $request->session()->put('admin_auth_token_version', $admin->auth_token_version);

        $this->auditLogger->logAdminEvent('admin.2fa.enrolled', $admin);

        // Flash recovery codes for single-view presentation
        return redirect()->route('admin.dashboard')->with([
            'recovery_codes' => $plainRecoveryCodes,
            'status' => 'Two-factor authentication successfully enabled. Store your recovery codes in a secure password manager.',
        ]);
    }
}
