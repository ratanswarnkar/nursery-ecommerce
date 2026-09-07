<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use Illuminate\Support\Str;

class PaymentTransactionNumberGenerator
{
    /**
     * Generate a unique, non-sequential public transaction number.
     * Format: PAY-YYYYMMDD-XXXXXX (e.g. PAY-20260907-A9X2M1)
     */
    public function generate(): string
    {
        $prefix = 'PAY-'.now()->format('Ymd').'-';

        do {
            $randomSuffix = strtoupper(Str::random(6));
            $transactionNumber = $prefix.$randomSuffix;
        } while (PaymentTransaction::where('transaction_number', $transactionNumber)->exists());

        return $transactionNumber;
    }
}
