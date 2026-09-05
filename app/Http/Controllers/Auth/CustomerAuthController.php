<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerOtpRequest;
use App\Http\Requests\Auth\CustomerOtpVerifyRequest;
use App\Models\Customer;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\OtpServiceInterface;
use App\Services\Auth\PhoneNumberNormalizer;
use App\Services\Cart\CartMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class CustomerAuthController extends Controller
{
    public function __construct(
        private PhoneNumberNormalizer $phoneNormalizer,
        private OtpServiceInterface $otpService,
        private AuditLogger $auditLogger,
        private CartMergeService $cartMergeService
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.customer.login');
    }

    public function requestOtp(CustomerOtpRequest $request): RedirectResponse
    {
        try {
            $normalizedPhone = $this->phoneNormalizer->normalize($request->phone);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        // Generate challenge (enforces cooldown, invalidates prior active challenges)
        $this->otpService->generate($normalizedPhone, $request->ip(), $request->userAgent());

        $this->auditLogger->logSecurityEvent('customer.otp.requested', [
            'phone' => $normalizedPhone,
        ]);

        return back()->with([
            'status' => 'OTP has been sent to your mobile number.',
            'phone' => $normalizedPhone,
            'otp_requested' => true,
        ]);
    }

    public function verifyOtp(CustomerOtpVerifyRequest $request): RedirectResponse
    {
        try {
            $normalizedPhone = $this->phoneNormalizer->normalize($request->phone);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        $isValid = $this->otpService->verify($normalizedPhone, $request->otp);

        if (! $isValid) {
            $this->auditLogger->logSecurityEvent('customer.otp.failure', [
                'phone' => $normalizedPhone,
            ]);

            throw ValidationException::withMessages([
                'otp' => ['Invalid, expired, or blocked verification code. Please request a new OTP.'],
            ]);
        }

        // Find or create customer account
        $customer = Customer::where('phone', $normalizedPhone)->first();

        if ($customer && ! $customer->is_active) {
            $this->auditLogger->logSecurityEvent('customer.login.blocked', [
                'phone' => $normalizedPhone,
            ]);

            throw ValidationException::withMessages([
                'phone' => ['Your account has been deactivated or blocked. Please contact support.'],
            ]);
        }

        if (! $customer) {
            $customer = Customer::create([
                'name' => 'Customer',
                'phone' => $normalizedPhone,
                'is_active' => true,
                'auth_token_version' => 1,
            ]);
        }

        // Capture guest session ID and cart ID before session regeneration to ensure cart merge works
        $guestSessionId = $request->session()->getId();
        $guestCartId = $request->session()->get('cart_id');

        // Complete customer authentication
        Auth::guard('customer')->login($customer);

        $request->session()->regenerate();
        $request->session()->put('customer_auth_token_version', $customer->auth_token_version);

        // Merge guest cart into customer cart transactionally
        $this->cartMergeService->mergeGuestCartIntoCustomerCart($guestSessionId, $customer, $guestCartId);

        $this->auditLogger->logCustomerEvent('customer.login.success', $customer, [
            'phone' => $normalizedPhone,
        ]);

        return redirect()->intended(route('customer.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if ($customer instanceof Customer) {
            $this->auditLogger->logCustomerEvent('customer.logout', $customer);
        }

        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login')->with('status', 'You have been logged out successfully.');
    }
}
