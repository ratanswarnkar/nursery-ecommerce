<?php

namespace App\Exceptions\Payment;

use DomainException;

class PaymentAmountMismatchException extends DomainException
{
    public function __construct(
        public readonly string $expectedAmount,
        public readonly string $actualAmount,
        string $message = ''
    ) {
        $msg = $message ?: "Payment amount mismatch: expected '{$expectedAmount}', received '{$actualAmount}'.";
        parent::__construct($msg);
    }
}
