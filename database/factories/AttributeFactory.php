<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'code' => Str::slug($name).'_'.fake()->unique()->randomNumber(3),
            'type' => AttributeType::SELECT,
            'is_required' => false,
            'is_filterable' => true,
            'sort_order' => 0,
        ];
    }
}
