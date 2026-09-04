<?php

namespace Database\Factories;

use App\Enums\TenderBillStatus;
use App\Models\Tender;
use App\Models\TenderBill;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderBillFactory extends Factory
{
    protected $model = TenderBill::class;

    public function definition(): array
    {
        $subtotal = 5500.00;
        $tax = 990.00;
        $discount = 0.00;
        $grandTotal = $subtotal + $tax - $discount;

        return [
            'tender_id' => Tender::factory(),
            'tender_requirement_id' => null,
            'bill_number' => 'TBILL-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'bill_date' => now()->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'grand_total' => $grandTotal,
            'status' => TenderBillStatus::DRAFT,
            'notes' => '1st Running Account Bill for Landscape Works',
            'source_type' => 'requirement',
            'metadata' => ['ra_bill_number' => '1st RA Bill'],
        ];
    }
}
