<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminForgotPasswordRequest;
use App\Http\Requests\Auth\AdminResetPasswordRequest;
use App\Models\Admin;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminPasswordResetController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    public function showForgotPasswordForm(): View
    {
        return view('auth.admin.forgot-password');
    }

    public function sendResetLinkEmail(AdminForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('admins')->sendResetLink(
            $request->only('email')
        );

        $this->auditLogger->logSecurityEvent('admin.password_reset.requested', [
            'email' => $request->email,
        ]);

        // Generic response prevents account enumeration
        return back()->with('status', 'If an account matches that email address, a password reset link has been sent.');
    }

    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('auth.admin.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(AdminResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin, string $password) {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    // Deterministically invalidate existing active sessions across all devices
                    'auth_token_version' => $admin->auth_token_version + 1,
                ])->save();

                event(new PasswordReset($admin));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $admin = Admin::where('email', $request->email)->first();
        if ($admin) {
            $this->auditLogger->logAdminEvent('admin.password_reset.completed', $admin, [
                'new_version' => $admin->auth_token_version,
            ]);
        }

        return redirect()->route('admin.login')->with('status', 'Your password has been successfully reset. Please log in with your new credentials.');
    }
}
