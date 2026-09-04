<?php

namespace Database\Factories;

use App\Enums\TenderRequirementSourceType;
use App\Enums\TenderRequirementStatus;
use App\Models\Tender;
use App\Models\TenderRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderRequirementFactory extends Factory
{
    protected $model = TenderRequirement::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'requirement_number' => 'REQ-'.fake()->unique()->numerify('####'),
            'requirement_date' => now()->toDateString(),
            'source_document_id' => null,
            'notes' => 'Demand note for North Wing landscaping section',
            'status' => TenderRequirementStatus::RECEIVED,
            'source_type' => TenderRequirementSourceType::MANUAL,
            'metadata' => ['department_officer' => 'Executive Engineer Horticulture'],
        ];
    }
}
