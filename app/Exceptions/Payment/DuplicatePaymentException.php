<?php

namespace App\Exceptions\Payment;

use DomainException;

class DuplicatePaymentException extends DomainException
{
    public function __construct(
        public readonly string $idempotencyKey,
        string $message = ''
    ) {
        $msg = $message ?: "Duplicate payment attempt detected with idempotency key '{$idempotencyKey}'.";
        parent::__construct($msg);
    }
}
