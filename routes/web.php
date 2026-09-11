<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminOrderReturnController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AdminPasswordResetController;
use App\Http\Controllers\Auth\AdminTwoFactorController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Customer\AccountDashboardController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CustomerReturnController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Storefront\BrandPageController;
use App\Http\Controllers\Storefront\CategoryPageController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PolicyController;
use App\Http\Controllers\Storefront\ProductDetailController;
use App\Http\Controllers\Storefront\ServiceController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Storefront Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/categories/{category:slug}', [CategoryPageController::class, 'show'])->name('categories.show');
Route::get('/brands/{brand:slug}', [BrandPageController::class, 'show'])->name('brands.show');
Route::get('/products/{product:slug}', [ProductDetailController::class, 'show'])->name('products.show');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Public Services
Route::get('/services/landscaping', [ServiceController::class, 'landscaping'])->name('services.landscaping');

// Public Policy & Legal Routes
Route::get('/privacy-policy', [PolicyController::class, 'privacy'])->name('policy.privacy');
Route::get('/terms-and-conditions', [PolicyController::class, 'terms'])->name('policy.terms');
Route::get('/shipping-policy', [PolicyController::class, 'shipping'])->name('policy.shipping');
Route::get('/cancellation-and-refund-policy', [PolicyController::class, 'refund'])->name('policy.refund');
Route::get('/contact-us', [PolicyController::class, 'contact'])->name('policy.contact');

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

    // Cart Routes (Guest & Customer)
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::put('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    // Customer Protected Account Portal
    Route::middleware(['auth:customer', 'ensure.active:customer', 'verify.session.version:customer'])->group(function () {
        Route::get('/account', [AccountDashboardController::class, 'index'])->name('customer.home');
        Route::get('/account/dashboard', [AccountDashboardController::class, 'index'])->name('account.dashboard');

        // Customer Profile
        Route::get('/account/profile', [ProfileController::class, 'edit'])->name('account.profile');
        Route::put('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');

        // Customer Addresses (IDOR-Protected)
        Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
        Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
        Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
        Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
        Route::post('/account/addresses/{address}/set-default', [AddressController::class, 'setDefault'])->name('account.addresses.set-default');

        // Customer Checkout Routes
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->middleware('throttle:customer-checkout')
            ->name('checkout.store');
        Route::get('/checkout/payment/{order_number}', [CheckoutController::class, 'payment'])->name('checkout.payment');
        Route::post('/checkout/payment/verify', [CheckoutController::class, 'verifyPayment'])
            ->middleware('throttle:payment-verify')
            ->name('checkout.payment.verify');
        Route::post('/checkout/payment/cancel', [CheckoutController::class, 'cancelPayment'])
            ->middleware('throttle:payment-cancel')
            ->name('checkout.payment.cancel');
        Route::get('/checkout/success/{order_number}', [CheckoutController::class, 'success'])->name('checkout.success');

        // Customer Orders (IDOR-Protected)
        Route::get('/account/orders', [CustomerOrderController::class, 'index'])->name('account.orders.index');
        Route::get('/account/orders/{order_number}', [CustomerOrderController::class, 'show'])->name('account.orders.show');
        Route::get('/account/orders/{order_number}/invoice', [CustomerOrderController::class, 'invoice'])->name('account.orders.invoice');
        Route::get('/account/orders/{order_number}/return', [CustomerReturnController::class, 'create'])->name('account.orders.return');
        Route::post('/account/orders/{order_number}/return', [CustomerReturnController::class, 'store'])->name('account.orders.return.store');
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

        // Warehouses
        Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('permission:warehouses.view,admin')->name('warehouses.index');
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->middleware('permission:warehouses.create,admin')->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('permission:warehouses.create,admin')->name('warehouses.store');
        Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->middleware('permission:warehouses.update,admin')->name('warehouses.edit');
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('permission:warehouses.update,admin')->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('permission:warehouses.delete,admin')->name('warehouses.destroy');
        Route::post('/warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->middleware('permission:warehouses.update,admin')->name('warehouses.toggle-status');

        // Inventory
        Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view,admin')->name('inventory.index');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.manage,admin')->name('inventory.adjust');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view,admin')->name('inventory.movements');

        // Orders (RBAC Protected)
        Route::get('/orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view,admin')->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view,admin')->name('orders.show');
        Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->middleware('permission:orders.view,admin')->name('orders.invoice');
        Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.update,admin')->name('orders.update-status');
        Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->middleware('permission:orders.cancel,admin')->name('orders.cancel');
        Route::post('/orders/{order}/shipments', [AdminOrderController::class, 'createShipment'])->middleware('permission:orders.update,admin')->name('orders.shipments.create');
        Route::put('/orders/{order}/shipments/{shipment}', [AdminOrderController::class, 'updateShipment'])->middleware('permission:orders.update,admin')->name('orders.shipments.update');
        Route::post('/orders/{order}/shipments/{shipment}/out-for-delivery', [AdminOrderController::class, 'markOutForDelivery'])->middleware('permission:orders.update,admin')->name('orders.shipments.out-for-delivery');
        Route::post('/orders/{order}/shipments/{shipment}/delivered', [AdminOrderController::class, 'markDelivered'])->middleware('permission:orders.update,admin')->name('orders.shipments.delivered');

        // Order Returns & Refunds (RBAC Protected)
        Route::post('/orders/{order}/returns/{orderReturn}/approve', [AdminOrderReturnController::class, 'approve'])->middleware('permission:orders.update,admin')->name('orders.returns.approve');
        Route::post('/orders/{order}/returns/{orderReturn}/reject', [AdminOrderReturnController::class, 'reject'])->middleware('permission:orders.update,admin')->name('orders.returns.reject');
        Route::post('/orders/{order}/returns/{orderReturn}/complete', [AdminOrderReturnController::class, 'complete'])->middleware('permission:orders.update,admin')->name('orders.returns.complete');
        Route::post('/orders/{order}/returns/{orderReturn}/restock', [AdminOrderReturnController::class, 'restock'])->middleware('permission:orders.update,admin')->name('orders.returns.restock');
        Route::post('/orders/{order}/refunds', [AdminOrderReturnController::class, 'processRefund'])->middleware('permission:orders.update,admin')->name('orders.refunds.process');

        // Granular RBAC Demonstration Routes
        Route::get('/test/orders-view', function () {
            return response()->json(['access' => 'granted', 'permission' => 'orders.view']);
        })->middleware('permission:orders.view');

        Route::get('/test/roles-manage', function () {
            return response()->json(['access' => 'granted', 'permission' => 'roles.manage']);
        })->middleware('permission:roles.manage');
    });
});
