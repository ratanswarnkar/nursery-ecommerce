<?php

namespace App\Exceptions\Invoice;

use RuntimeException;

class IneligibleForInvoiceException extends RuntimeException
{
    public static function forOrder(string $orderNumber, string $reason): self
    {
        return new self("Tax invoice cannot be generated for order {$orderNumber}: {$reason}");
    }
}
