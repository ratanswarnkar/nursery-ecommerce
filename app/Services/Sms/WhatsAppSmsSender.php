<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppSmsSender implements SmsSenderInterface
{
    protected string $apiUrl;

    protected string $apiKey;

    protected string $sender;

    protected ?string $footer;

    protected int $timeout;

    protected int $connectTimeout;

    public function __construct(
        ?string $apiUrl = null,
        ?string $apiKey = null,
        ?string $sender = null,
        ?string $footer = null,
        ?int $timeout = null,
        ?int $connectTimeout = null,
    ) {
        $config = config('services.sms.whatsapp') ?? config('services.whatsapp') ?? [];

        $this->apiUrl = $apiUrl ?? $config['api_url'] ?? 'https://whatsapp.myapi.in.net/send-message';
        $this->apiKey = $apiKey ?? $config['api_key'] ?? '';
        $this->sender = $sender ?? $config['sender'] ?? '919811114365';
        $this->footer = $footer ?? $config['footer'] ?? null;
        $this->timeout = $timeout ?? (int) ($config['timeout'] ?? 10);
        $this->connectTimeout = $connectTimeout ?? (int) ($config['connect_timeout'] ?? 5);
    }

    /**
     * Send an OTP/SMS message via WhatsApp HTTP provider.
     *
     * @param  string  $phoneE164  Target phone number.
     * @param  string  $message  Text message content containing OTP.
     * @return bool True if provider acknowledges successful dispatch (status: true), false otherwise.
     */
    public function send(string $phoneE164, string $message): bool
    {
        if (empty($this->apiKey) || empty($this->apiUrl) || empty($this->sender)) {
            Log::error('WhatsApp OTP dispatch failed: Missing provider configuration credentials.');

            return false;
        }

        $formattedNumber = $this->formatPhoneNumber($phoneE164);
        $maskedRecipient = $this->maskPhone($formattedNumber);

        $payload = [
            'api_key' => $this->apiKey,
            'sender' => $this->sender,
            'number' => $formattedNumber,
            'message' => $message,
        ];

        if (! empty($this->footer)) {
            $payload['footer'] = $this->footer;
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->post($this->apiUrl, $payload);

            if (! $response->successful()) {
                Log::warning('WhatsApp OTP provider HTTP error', [
                    'status_code' => $response->status(),
                    'recipient' => $maskedRecipient,
                ]);

                return false;
            }

            $data = $response->json();

            if (! is_array($data)) {
                Log::warning('WhatsApp OTP provider returned non-JSON response', [
                    'recipient' => $maskedRecipient,
                ]);

                return false;
            }

            if (($data['status'] ?? false) !== true) {
                Log::warning('WhatsApp OTP provider reported unsuccessful dispatch', [
                    'recipient' => $maskedRecipient,
                    'msg' => $data['msg'] ?? null,
                ]);

                return false;
            }

            Log::info('WhatsApp OTP successfully dispatched', [
                'recipient' => $maskedRecipient,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('WhatsApp OTP dispatch exception', [
                'recipient' => $maskedRecipient,
                'exception' => get_class($e),
            ]);

            return false;
        }
    }

    /**
     * Format a phone number to international digits without '+' symbol.
     * Accurately converts 10-digit Indian numbers, e.g. 9811114365 -> 919811114365,
     * while avoiding duplicate 91 prefixes if already present.
     */
    public function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        // 10-digit standard Indian mobile number (e.g., 9811114365 -> 919811114365)
        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        // Already has 91 prefix with 10 following digits (12 digits total)
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        // Any other international number without leading '+'
        return $digits;
    }

    /**
     * Mask phone number for privacy in application logs.
     */
    protected function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 4) {
            return '***';
        }

        $prefix = substr($phone, 0, min(4, $len - 3));
        $suffix = substr($phone, -3);

        return $prefix.str_repeat('*', max(2, $len - strlen($prefix) - 3)).$suffix;
    }
}
