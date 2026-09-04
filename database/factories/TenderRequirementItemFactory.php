<?php

namespace Database\Factories;

use App\Enums\TenderRequirementMatchingStatus;
use App\Models\TenderRequirement;
use App\Models\TenderRequirementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderRequirementItemFactory extends Factory
{
    protected $model = TenderRequirementItem::class;

    public function definition(): array
    {
        $qty = 25.00;
        $rate = 220.00;
        $amount = $qty * $rate;

        return [
            'tender_requirement_id' => TenderRequirement::factory(),
            'tender_item_id' => null,
            'product_id' => null,
            'product_variant_id' => null,
            'item_code' => 'SOQ-001',
            'description' => 'Ficus Benjamina 4-5 feet height',
            'unit' => 'NOS',
            'quantity' => $qty,
            'matched_rate' => $rate,
            'amount' => $amount,
            'matching_status' => TenderRequirementMatchingStatus::MATCHED,
            'metadata' => ['delivery_block' => 'Block A'],
        ];
    }
}
