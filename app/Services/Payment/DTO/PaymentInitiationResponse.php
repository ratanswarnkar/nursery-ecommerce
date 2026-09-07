<?php

namespace App\Services\Payment\DTO;

class PaymentInitiationResponse
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionNumber,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $gatewayReference = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawPayload = []
    ) {}
}
