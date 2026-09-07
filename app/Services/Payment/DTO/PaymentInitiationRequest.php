<?php

namespace App\Services\Payment\DTO;

use App\Models\Customer;
use App\Models\Order;

class PaymentInitiationRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $transactionNumber,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $returnUrl,
        public readonly ?Customer $customer = null,
        public readonly array $metadata = []
    ) {}
}
