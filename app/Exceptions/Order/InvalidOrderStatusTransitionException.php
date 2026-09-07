<?php

namespace App\Exceptions\Order;

use App\Enums\OrderStatus;
use DomainException;

class InvalidOrderStatusTransitionException extends DomainException
{
    public function __construct(
        public readonly OrderStatus $fromStatus,
        public readonly OrderStatus $toStatus,
        string $message = ''
    ) {
        $msg = $message ?: "Invalid order status transition from '{$fromStatus->value}' to '{$toStatus->value}'.";
        parent::__construct($msg);
    }
}
