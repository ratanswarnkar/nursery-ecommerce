<?php

namespace App\Providers;

use App\Models\Admin;
use App\Services\Auth\OtpService;
use App\Services\Auth\OtpServiceInterface;
use App\Services\Auth\PhoneNumberNormalizer;
use App\Services\Invoice\InvoiceNumberGenerator;
use App\Services\Invoice\InvoiceService;
use App\Services\Order\OrderLifecycleService;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\RazorpayPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentTransactionNumberGenerator;
use App\Services\Shipping\ShipmentService;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\NullSmsSender;
use App\Services\Sms\SmsSenderInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PhoneNumberNormalizer::class);

        $this->app->singleton(SmsSenderInterface::class, function () {
            $driver = config('services.sms.driver', 'log');

            return match ($driver) {
                'null' => new NullSmsSender,
                default => new LogSmsSender,
            };
        });

        $this->app->singleton(OtpServiceInterface::class, OtpService::class);

        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(PaymentTransactionNumberGenerator::class);
        $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
            return $app->make(PaymentGatewayManager::class)->gateway();
        });
        $this->app->singleton(RazorpayPaymentGateway::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(OrderLifecycleService::class);
        $this->app->singleton(InvoiceNumberGenerator::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(ShipmentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureSuperAdminGate();
    }

    private function configureRateLimiting(): void
    {
        // Customer OTP generation: 3 requests per 10 minutes per phone + IP
        RateLimiter::for('customer-otp-request', function (Request $request) {
            $phone = (string) $request->input('phone', '');

            return Limit::perMinutes(10, 3)->by($phone.'|'.$request->ip());
        });

        // Customer OTP verification: 5 attempts per 5 minutes per phone + IP
        RateLimiter::for('customer-otp-verify', function (Request $request) {
            $phone = (string) $request->input('phone', '');

            return Limit::perMinutes(5, 5)->by($phone.'|'.$request->ip());
        });

        // Admin login: 5 attempts per minute per email + IP
        RateLimiter::for('admin-login', function (Request $request) {
            $email = (string) $request->input('email', '');

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        // Admin 2FA: 5 attempts per 5 minutes per pending admin ID + IP
        RateLimiter::for('admin-2fa', function (Request $request) {
            $adminId = $request->session()->get('admin_2fa_pending.admin_id', 'unknown');

            return Limit::perMinutes(5, 5)->by($adminId.'|'.$request->ip());
        });

        // Admin password reset: 3 attempts per 15 minutes per email + IP
        RateLimiter::for('admin-password-reset', function (Request $request) {
            $email = (string) $request->input('email', '');

            return Limit::perMinutes(15, 3)->by($email.'|'.$request->ip());
        });

        // Customer checkout creation: 5 requests per minute per customer ID or IP
        RateLimiter::for('customer-checkout', function (Request $request) {
            $customer = auth('customer')->user();
            $key = $customer ? "customer:{$customer->id}" : ('ip:'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });

        // Payment verification: 10 requests per minute per customer ID or IP
        RateLimiter::for('payment-verify', function (Request $request) {
            $customer = auth('customer')->user();
            $key = $customer ? "customer:{$customer->id}" : ('ip:'.$request->ip());

            return Limit::perMinute(10)->by($key);
        });

        // Payment cancellation: 10 requests per minute per customer ID or IP
        RateLimiter::for('payment-cancel', function (Request $request) {
            $customer = auth('customer')->user();
            $key = $customer ? "customer:{$customer->id}" : ('ip:'.$request->ip());

            return Limit::perMinute(10)->by($key);
        });
    }

    private function configureSuperAdminGate(): void
    {
        // Implicitly grant 'Super Admin' role all permissions
        Gate::before(function ($user, $ability) {
            return ($user instanceof Admin && $user->hasRole('Super Admin')) ? true : null;
        });
    }
}
