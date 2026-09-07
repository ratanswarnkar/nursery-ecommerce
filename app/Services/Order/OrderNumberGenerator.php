<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderNumberGenerator
{
    /**
     * Generate a unique, non-sequential public order number.
     * Format: ORD-YYYYMMDD-XXXXXX (e.g. ORD-20260907-7K9M2P)
     */
    public function generate(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';

        do {
            $randomSuffix = strtoupper(Str::random(6));
            // Ensure no confusing characters if desired, or standard alphanumeric
            $orderNumber = $prefix.$randomSuffix;
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
