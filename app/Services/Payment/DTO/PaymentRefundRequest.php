<?php

namespace App\Services\Payment\DTO;

use App\Models\Order;
use App\Models\PaymentTransaction;

class PaymentRefundRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly Order $order,
        public readonly PaymentTransaction $transaction,
        public readonly string $gatewayPaymentId,
        public readonly string $amount,
        public readonly string $currency = 'INR',
        public readonly ?string $reason = null,
        public readonly array $metadata = []
    ) {}
}
