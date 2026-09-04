<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPending2fa
{
    public function handle(Request $request, Closure $next): Response
    {
        $pending = $request->session()->get('admin_2fa_pending');

        if (! $pending || ! isset($pending['admin_id']) || ! isset($pending['expires_at'])) {
            $request->session()->forget('admin_2fa_pending');

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Your authentication session has expired. Please enter your credentials again.',
            ]);
        }

        if (now()->timestamp > $pending['expires_at']) {
            $request->session()->forget('admin_2fa_pending');
            $request->session()->forget('admin_2fa_enrollment');

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Your two-factor verification window has expired. Please log in again.',
            ]);
        }

        $admin = Admin::find($pending['admin_id']);

        if (! $admin || ! $admin->is_active) {
            $request->session()->forget('admin_2fa_pending');
            $request->session()->forget('admin_2fa_enrollment');

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Account is inactive or not found.',
            ]);
        }

        // Attach pending admin instance to request for controller access
        $request->attributes->set('pending_admin', $admin);

        return $next($request);
    }
}
