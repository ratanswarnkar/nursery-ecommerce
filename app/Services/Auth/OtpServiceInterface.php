<?php

namespace App\Services\Auth;

use App\Models\CustomerOtpChallenge;

interface OtpServiceInterface
{
    /**
     * Generate and dispatch a new OTP challenge.
     * Enforces resend cooldown and invalidates prior unverified challenges.
     */
    public function generate(string $phoneE164, ?string $ipAddress = null, ?string $userAgent = null): CustomerOtpChallenge;

    /**
     * Verify an OTP code against the latest active challenge for the phone.
     * Returns true on success, false on failure or attempt limit exceeded.
     */
    public function verify(string $phoneE164, string $otp): bool;

    /**
     * Check if a phone number is currently in resend cooldown.
     */
    public function isInResendCooldown(string $phoneE164): bool;
}
