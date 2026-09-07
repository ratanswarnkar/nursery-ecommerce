<?php

namespace App\Services\Payment\DTO;

use App\Enums\PaymentStatus;

class PaymentStatusResponse
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $gatewayTransactionId = null,
        public readonly ?string $amount = null,
        public readonly array $rawPayload = []
    ) {}
}
