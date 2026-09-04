<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminPending2fa;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\ValidateSessionVersion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ensure.admin.pending_2fa' => EnsureAdminPending2fa::class,
            'ensure.active' => EnsureAccountIsActive::class,
            'verify.session.version' => ValidateSessionVersion::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->append(SecurityHeadersMiddleware::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin*') || $request->is('admin-portal*')) {
                return route('admin.login');
            }

            return route('customer.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
