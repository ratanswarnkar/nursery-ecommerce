<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AdminPasswordResetController;
use App\Http\Controllers\Auth\AdminTwoFactorController;
use App\Http\Controllers\Auth\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Customer Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('web')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
    Route::post('/otp/request', [CustomerAuthController::class, 'requestOtp'])
        ->middleware('throttle:customer-otp-request')
        ->name('customer.otp.request');
    Route::post('/otp/verify', [CustomerAuthController::class, 'verifyOtp'])
        ->middleware('throttle:customer-otp-verify')
        ->name('customer.otp.verify');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

    // Customer Protected Test Area
    Route::middleware(['auth:customer', 'ensure.active:customer', 'verify.session.version:customer'])->group(function () {
        Route::get('/account', function () {
            return response()->json([
                'status' => 'authenticated',
                'guard' => 'customer',
                'customer' => auth('customer')->user(),
            ]);
        })->name('customer.home');
    });
});

/*
|--------------------------------------------------------------------------
| Administrator Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {
    // Password login
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:admin-login')
        ->name('login.submit');

    // Password reset
    Route::get('/forgot-password', [AdminPasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AdminPasswordResetController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:admin-password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AdminPasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AdminPasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:admin-password-reset')
        ->name('password.update');

    // Two-Factor Authentication (guarded by pending 2FA state)
    Route::middleware('ensure.admin.pending_2fa')->group(function () {
        Route::get('/2fa/challenge', [AdminTwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
        Route::post('/2fa/verify', [AdminTwoFactorController::class, 'verify'])
            ->middleware('throttle:admin-2fa')
            ->name('2fa.verify');
        Route::get('/2fa/enroll', [AdminTwoFactorController::class, 'showEnroll'])->name('2fa.enroll');
        Route::post('/2fa/enroll', [AdminTwoFactorController::class, 'confirmEnroll'])->name('2fa.enroll.confirm');
    });

    // Fully Authenticated Admin Protected Routes
    Route::middleware(['auth:admin', 'ensure.active:admin', 'verify.session.version:admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AdminAuthController::class, 'logoutAll'])->name('logout.all');

        // Verification endpoint for admin authentication
        Route::get('/dashboard', function () {
            return response()->json([
                'status' => 'authenticated',
                'guard' => 'admin',
                'admin' => auth('admin')->user(),
            ]);
        })->name('dashboard');

        // Granular RBAC Demonstration Routes
        Route::get('/test/orders-view', function () {
            return response()->json(['access' => 'granted', 'permission' => 'orders.view']);
        })->middleware('permission:orders.view');

        Route::get('/test/roles-manage', function () {
            return response()->json(['access' => 'granted', 'permission' => 'roles.manage']);
        })->middleware('permission:roles.manage');
    });
});
