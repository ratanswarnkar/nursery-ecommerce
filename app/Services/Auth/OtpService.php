<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\CustomerOtpChallenge;
use App\Services\Sms\SmsSenderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService implements OtpServiceInterface
{
    public function __construct(
        private SmsSenderInterface $smsSender
    ) {}

    /**
     * Generate and dispatch a new OTP challenge.
     * Enforces resend cooldown and invalidates prior unverified challenges.
     */
    public function generate(string $phoneE164, ?string $ipAddress = null, ?string $userAgent = null): CustomerOtpChallenge
    {
        // 1. Check resend cooldown
        if ($this->isInResendCooldown($phoneE164)) {
            $latest = CustomerOtpChallenge::where('phone_e164', $phoneE164)->latest('id')->first();
            $secondsRemaining = max(1, $latest->resend_available_at->diffInSeconds(now()));

            throw ValidationException::withMessages([
                'phone' => ["Please wait {$secondsRemaining} seconds before requesting a new OTP."],
            ]);
        }

        return DB::transaction(function () use ($phoneE164, $ipAddress, $userAgent) {
            // 2. Mandatory Rule: Invalidate all prior active challenges for this phone
            CustomerOtpChallenge::where('phone_e164', $phoneE164)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);

            // 3. Cryptographically secure 6-digit OTP
            $plainOtp = (string) random_int(100000, 999999);

            // Find associated customer if registered
            $customer = Customer::where('phone', $phoneE164)->first();

            // 4. Create challenge with hashed OTP, 5-minute expiry, and 60s cooldown
            $challenge = CustomerOtpChallenge::create([
                'customer_id' => $customer?->id,
                'phone_e164' => $phoneE164,
                'otp_hash' => Hash::make($plainOtp),
                'expires_at' => now()->addMinutes(5),
                'resend_available_at' => now()->addSeconds(60),
                'attempts' => 0,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            // 5. Send via SMS / WhatsApp abstraction
            $message = "Your Sugandha Farms & Nursery verification code is {$plainOtp}. Valid for 5 minutes. Please do not share this code with anyone.";
            $sent = $this->smsSender->send($phoneE164, $message);

            if (! $sent) {
                throw ValidationException::withMessages([
                    'phone' => ['Failed to send verification code. Please try again.'],
                ]);
            }

            return $challenge;
        });
    }

    /**
     * Verify an OTP code against the latest active challenge for the phone.
     */
    public function verify(string $phoneE164, string $otp): bool
    {
        return DB::transaction(function () use ($phoneE164, $otp) {
            // Retrieve latest active challenge with row locking
            $challenge = CustomerOtpChallenge::where('phone_e164', $phoneE164)
                ->whereNull('verified_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $challenge) {
                return false;
            }

            // If maximum 3 attempts exceeded, challenge is permanently blocked
            if ($challenge->attempts >= 3) {
                return false;
            }

            // Constant-time / secure hash comparison
            if (Hash::check($otp, $challenge->otp_hash)) {
                $challenge->update([
                    'verified_at' => now(),
                ]);

                return true;
            }

            // Increment attempt count on failure
            $challenge->increment('attempts');

            return false;
        });
    }

    /**
     * Determine if a phone number is currently in resend cooldown.
     */
    public function isInResendCooldown(string $phoneE164): bool
    {
        $latest = CustomerOtpChallenge::where('phone_e164', $phoneE164)
            ->latest('id')
            ->first();

        if (! $latest) {
            return false;
        }

        return $latest->resend_available_at->isFuture();
    }
}
