<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class InvoiceNumberGenerator
{
    /**
     * Generate a collision-safe, unique invoice number.
     * Format: INV-YYYYMMDD-XXXXXX
     */
    public function generate(?Carbon $date = null): string
    {
        $dateStr = ($date ?? Carbon::now())->format('Ymd');
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $randomPart = strtoupper(Str::random(6));
            $candidate = "INV-{$dateStr}-{$randomPart}";

            if (! Invoice::where('invoice_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Failed to generate a unique invoice number after maximum attempts.');
    }
}
