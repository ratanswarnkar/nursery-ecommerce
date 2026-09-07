<?php

namespace App\Exceptions\Payment;

use App\Enums\PaymentStatus;
use DomainException;

class InvalidPaymentTransitionException extends DomainException
{
    public function __construct(
        public readonly PaymentStatus $fromStatus,
        public readonly PaymentStatus $toStatus,
        string $message = ''
    ) {
        $msg = $message ?: "Invalid payment status transition from '{$fromStatus->value}' to '{$toStatus->value}'.";
        parent::__construct($msg);
    }
}
