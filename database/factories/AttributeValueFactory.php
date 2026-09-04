<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        $val = fake()->word();

        return [
            'attribute_id' => Attribute::factory(),
            'value' => strtolower($val),
            'label' => ucfirst($val),
            'sort_order' => 0,
        ];
    }
}
