<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
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

        // Admin Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view,admin')
            ->name('dashboard');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view,admin')->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->middleware('permission:categories.create,admin')->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create,admin')->name('categories.store');
        Route::get('/categories/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view,admin')->name('categories.show');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->middleware('permission:categories.update,admin')->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update,admin')->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete,admin')->name('categories.destroy');
        Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->middleware('permission:categories.update,admin')->name('categories.toggle-status');

        // Brands
        Route::get('/brands', [BrandController::class, 'index'])->middleware('permission:brands.view,admin')->name('brands.index');
        Route::get('/brands/create', [BrandController::class, 'create'])->middleware('permission:brands.create,admin')->name('brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->middleware('permission:brands.create,admin')->name('brands.store');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->middleware('permission:brands.update,admin')->name('brands.edit');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->middleware('permission:brands.update,admin')->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:brands.delete,admin')->name('brands.destroy');
        Route::post('/brands/{brand}/toggle-status', [BrandController::class, 'toggleStatus'])->middleware('permission:brands.update,admin')->name('brands.toggle-status');

        // Attributes
        Route::get('/attributes', [AttributeController::class, 'index'])->middleware('permission:attributes.view,admin')->name('attributes.index');
        Route::get('/attributes/create', [AttributeController::class, 'create'])->middleware('permission:attributes.create,admin')->name('attributes.create');
        Route::post('/attributes', [AttributeController::class, 'store'])->middleware('permission:attributes.create,admin')->name('attributes.store');
        Route::get('/attributes/{attribute}/edit', [AttributeController::class, 'edit'])->middleware('permission:attributes.update,admin')->name('attributes.edit');
        Route::put('/attributes/{attribute}', [AttributeController::class, 'update'])->middleware('permission:attributes.update,admin')->name('attributes.update');
        Route::delete('/attributes/{attribute}', [AttributeController::class, 'destroy'])->middleware('permission:attributes.delete,admin')->name('attributes.destroy');

        // Attribute Values (Scoped under Attribute)
        Route::prefix('attributes/{attribute}')->name('attributes.values.')->scopeBindings()->group(function () {
            Route::get('/values', [AttributeValueController::class, 'index'])->middleware('permission:attributes.view,admin')->name('index');
            Route::post('/values', [AttributeValueController::class, 'store'])->middleware('permission:attributes.create,admin')->name('store');
            Route::put('/values/{value}', [AttributeValueController::class, 'update'])->middleware('permission:attributes.update,admin')->name('update');
            Route::delete('/values/{value}', [AttributeValueController::class, 'destroy'])->middleware('permission:attributes.delete,admin')->name('destroy');
        });

        // Products
        Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view,admin')->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:products.create,admin')->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create,admin')->name('products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view,admin')->name('products.show');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:products.update,admin')->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update,admin')->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete,admin')->name('products.destroy');
        Route::post('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('permission:products.update,admin')->name('products.toggle-status');

        // Product Variants (Scoped under Product)
        Route::prefix('products/{product}')->name('products.variants.')->scopeBindings()->group(function () {
            Route::get('/variants', [ProductVariantController::class, 'index'])->middleware('permission:products.view,admin')->name('index');
            Route::get('/variants/create', [ProductVariantController::class, 'create'])->middleware('permission:products.create,admin')->name('create');
            Route::post('/variants', [ProductVariantController::class, 'store'])->middleware('permission:products.create,admin')->name('store');
            Route::get('/variants/{variant}/edit', [ProductVariantController::class, 'edit'])->middleware('permission:products.update,admin')->name('edit');
            Route::put('/variants/{variant}', [ProductVariantController::class, 'update'])->middleware('permission:products.update,admin')->name('update');
            Route::delete('/variants/{variant}', [ProductVariantController::class, 'destroy'])->middleware('permission:products.delete,admin')->name('destroy');
            Route::post('/variants/{variant}/toggle-status', [ProductVariantController::class, 'toggleStatus'])->middleware('permission:products.update,admin')->name('toggle-status');
        });

        // Product Images (Scoped under Product)
        Route::prefix('products/{product}')->name('products.images.')->scopeBindings()->group(function () {
            Route::get('/images', [ProductImageController::class, 'index'])->middleware('permission:products.view,admin')->name('index');
            Route::post('/images', [ProductImageController::class, 'store'])->middleware('permission:products.update,admin')->name('store');
            Route::post('/images/{image}/set-primary', [ProductImageController::class, 'setPrimary'])->middleware('permission:products.update,admin')->name('set-primary');
            Route::post('/images/reorder', [ProductImageController::class, 'reorder'])->middleware('permission:products.update,admin')->name('reorder');
            Route::delete('/images/{image}', [ProductImageController::class, 'destroy'])->middleware('permission:products.delete,admin')->name('destroy');
        });

        // Granular RBAC Demonstration Routes
        Route::get('/test/orders-view', function () {
            return response()->json(['access' => 'granted', 'permission' => 'orders.view']);
        })->middleware('permission:orders.view');

        Route::get('/test/roles-manage', function () {
            return response()->json(['access' => 'granted', 'permission' => 'roles.manage']);
        })->middleware('permission:roles.manage');
    });
});
