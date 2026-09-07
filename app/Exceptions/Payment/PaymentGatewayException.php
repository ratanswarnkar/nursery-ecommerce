<?php

namespace App\Exceptions\Payment;

use RuntimeException;
use Throwable;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(
        string $message = 'Payment gateway error occurred.',
        public readonly ?string $gateway = null,
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
