<?php

namespace App\Services\Payment\DTO;

use App\Enums\RefundStatus;

class PaymentRefundResponse
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly bool $success,
        public readonly RefundStatus $status,
        public readonly ?string $gatewayRefundId = null,
        public readonly ?string $amount = null,
        public readonly ?string $currency = 'INR',
        public readonly ?string $failureCode = null,
        public readonly ?string $failureMessage = null,
        public readonly array $rawPayload = []
    ) {}
}
