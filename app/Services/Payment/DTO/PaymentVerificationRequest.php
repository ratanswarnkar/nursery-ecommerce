<?php

namespace App\Services\Payment\DTO;

class PaymentVerificationRequest
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        public readonly string $transactionNumber,
        public readonly array $payload = [],
        public readonly array $headers = []
    ) {}
}
