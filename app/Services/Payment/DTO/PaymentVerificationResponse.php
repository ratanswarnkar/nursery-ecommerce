<?php

namespace App\Services\Payment\DTO;

use App\Enums\PaymentStatus;

class PaymentVerificationResponse
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly bool $success,
        public readonly PaymentStatus $status,
        public readonly ?string $gatewayTransactionId = null,
        public readonly ?string $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null,
        public readonly array $rawPayload = []
    ) {}
}
