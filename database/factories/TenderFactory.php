<?php

namespace Database\Factories;

use App\Enums\TenderPricingMode;
use App\Enums\TenderStatus;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderFactory extends Factory
{
    protected $model = Tender::class;

    public function definition(): array
    {
        $soq = 5000000.00;
        $awarded = 4500000.00;
        $diffPercent = (($awarded - $soq) / $soq) * 100;

        return [
            'tender_number' => 'TND-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'name' => 'Horticulture & Landscape Beautification Project',
            'department_name' => 'Central Public Works Department (CPWD)',
            'project_name' => 'Govt Complex Greenscape Phase 2',
            'description' => 'Supply, planting and maintenance of indigenous shrubs, trees and ground cover.',
            'original_soq_value' => $soq,
            'awarded_value' => $awarded,
            'below_above_percentage' => $diffPercent,
            'pricing_mode' => TenderPricingMode::ITEM_WISE,
            'status' => TenderStatus::ACTIVE,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'special_billing_enabled' => false, // Default is false as strictly required
            'metadata' => ['contractor_registration_id' => 'REG-98765'],
        ];
    }
}
