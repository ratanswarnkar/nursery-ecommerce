<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSenderInterface
{
    /**
     * In-memory buffer for testing/verification inspection.
     */
    public static array $dispatchedMessages = [];

    public function send(string $phoneE164, string $message): bool
    {
        // Keep in-memory copy for testing/dev inspection
        static::$dispatchedMessages[] = [
            'phone' => $phoneE164,
            'message' => $message,
            'dispatched_at' => now(),
        ];

        // Production-safe log: never expose raw message or plain OTP in application logs
        if (app()->isProduction()) {
            Log::info("SMS notification dispatched to [{$phoneE164}]");
        } else {
            // In local/testing development environment, log dispatch without logging raw OTP directly
            Log::debug("Development SMS simulated for [{$phoneE164}]");
        }

        return true;
    }

    public static function clear(): void
    {
        static::$dispatchedMessages = [];
    }
}
