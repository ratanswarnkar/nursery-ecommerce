<?php

namespace Database\Factories;

use App\Models\Tender;
use App\Models\TenderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderItemFactory extends Factory
{
    protected $model = TenderItem::class;

    public function definition(): array
    {
        $qty = 100.00;
        $govtRate = 250.00;
        $govtAmount = $qty * $govtRate;
        $quotedRate = 220.00;
        $quotedAmount = $qty * $quotedRate;

        return [
            'tender_id' => Tender::factory(),
            'product_id' => null,
            'product_variant_id' => null,
            'item_code' => 'SOQ-'.fake()->unique()->numerify('###'),
            'description' => 'Supply and planting of Ficus Benjamina 4-5 feet height in 12 inch earthen pots',
            'unit' => 'NOS',
            'government_quantity' => $qty,
            'government_rate' => $govtRate,
            'government_amount' => $govtAmount,
            'quoted_rate' => $quotedRate,
            'quoted_amount' => $quotedAmount,
            'calculated_rate' => 220.00,
            'final_rate' => 220.00,
            'metadata' => ['specification_clause' => 'Clause 4.2.1'],
            'sort_order' => 1,
        ];
    }
}
