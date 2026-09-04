<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Models\Admin;
use App\Models\AdminLoginActivity;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.admin.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $admin = Admin::where('email', $request->email)->first();

        // Check credentials
        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            if ($admin) {
                AdminLoginActivity::create([
                    'admin_id' => $admin->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'login_at' => now(),
                    'is_successful' => false,
                    'failure_reason' => 'Invalid password',
                ]);
            }

            $this->auditLogger->logSecurityEvent('admin.login.failure', [
                'email' => $request->email,
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Account status check
        if (! $admin->is_active) {
            AdminLoginActivity::create([
                'admin_id' => $admin->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'login_at' => now(),
                'is_successful' => false,
                'failure_reason' => 'Account inactive',
            ]);

            $this->auditLogger->logAdminEvent('admin.login.blocked', $admin, [
                'reason' => 'account_inactive',
            ]);

            throw ValidationException::withMessages([
                'email' => ['Your administrator account is inactive or disabled. Contact system administrator.'],
            ]);
        }

        // Record successful password step
        AdminLoginActivity::create([
            'admin_id' => $admin->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => now(),
            'is_successful' => true,
            'failure_reason' => null,
        ]);

        $this->auditLogger->logAdminEvent('admin.password.verified', $admin);

        // Crucial: DO NOT log admin into guard yet. Put into temporary pending 2FA state.
        $request->session()->put('admin_2fa_pending', [
            'admin_id' => $admin->id,
            'auth_token_version' => $admin->auth_token_version,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        // Prevent session fixation
        $request->session()->regenerate();

        // Direct to enrollment if 2FA has never been confirmed
        if ($admin->two_factor_confirmed_at === null || empty($admin->two_factor_secret)) {
            return redirect()->route('admin.2fa.enroll');
        }

        return redirect()->route('admin.2fa.challenge');
    }

    public function logout(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if ($admin instanceof Admin) {
            $this->auditLogger->logAdminEvent('admin.logout', $admin);
        }

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been logged out successfully.');
    }

    public function logoutAll(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if ($admin instanceof Admin) {
            // Increment auth token version: deterministic invalidation across all devices
            $admin->increment('auth_token_version');

            $this->auditLogger->logAdminEvent('admin.sessions.invalidated', $admin, [
                'action' => 'logout_all_devices',
                'new_version' => $admin->auth_token_version,
            ]);
        }

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'All active sessions on all devices have been terminated.');
    }
}
