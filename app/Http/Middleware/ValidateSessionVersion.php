<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateSessionVersion
{
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $guardsToCheck = $guard ? [$guard] : ['admin', 'customer'];

        foreach ($guardsToCheck as $g) {
            if (Auth::guard($g)->check()) {
                $user = Auth::guard($g)->user();
                $sessionVersion = $request->session()->get("{$g}_auth_token_version");

                // If session version missing or mismatched with database version
                if ($sessionVersion === null || (int) $user->auth_token_version !== (int) $sessionVersion) {
                    Auth::guard($g)->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $loginRoute = $g === 'admin' ? 'admin.login' : 'customer.login';

                    return redirect()->route($loginRoute)->withErrors([
                        'message' => 'Your session has expired or was invalidated due to a security update. Please log in again.',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
