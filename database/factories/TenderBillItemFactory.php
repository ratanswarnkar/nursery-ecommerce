<?php

namespace Database\Factories;

use App\Models\TenderBill;
use App\Models\TenderBillItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderBillItemFactory extends Factory
{
    protected $model = TenderBillItem::class;

    public function definition(): array
    {
        $qty = 25.00;
        $rate = 220.00;
        $amount = $qty * $rate;

        return [
            'tender_bill_id' => TenderBill::factory(),
            'tender_item_id' => null,
            'requirement_item_id' => null,
            'item_code' => 'SOQ-001',
            'description' => 'Ficus Benjamina 4-5 feet height',
            'unit' => 'NOS',
            'quantity' => $qty,
            'rate' => $rate,
            'amount' => $amount,
            'metadata' => null,
        ];
    }
}
