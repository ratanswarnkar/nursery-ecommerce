<?php

namespace App\Exceptions\Shipping;

use DomainException;

class IneligibleForShipmentException extends DomainException
{
    public static function forOrder(string $orderNumber, string $reason): self
    {
        return new self("Cannot create shipment for order #{$orderNumber}: {$reason}");
    }
}
