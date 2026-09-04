<?php

namespace Database\Factories;

use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\TaxRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxRuleFactory extends Factory
{
    protected $model = TaxRule::class;

    public function definition(): array
    {
        return [
            'tax_class_id' => TaxClass::factory(),
            'tax_rate_id' => TaxRate::factory(),
            'country' => 'IN',
            'state' => null,
            'postal_code' => null,
            'priority' => 1,
            'is_active' => true,
        ];
    }
}
