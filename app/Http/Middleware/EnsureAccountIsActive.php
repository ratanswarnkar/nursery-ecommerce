<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $guardsToCheck = $guard ? [$guard] : ['admin', 'customer'];

        foreach ($guardsToCheck as $g) {
            if (Auth::guard($g)->check()) {
                $user = Auth::guard($g)->user();

                if ($user && ! $user->is_active) {
                    Auth::guard($g)->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $loginRoute = $g === 'admin' ? 'admin.login' : 'customer.login';

                    return redirect()->route($loginRoute)->withErrors([
                        'message' => 'Your account has been deactivated or suspended. Please contact support.',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
