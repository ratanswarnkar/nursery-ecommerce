<?php

namespace Database\Factories;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Rate',
            'rate' => fake()->randomElement([5.00, 12.00, 18.00, 28.00]),
            'is_active' => true,
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ];
    }
}
