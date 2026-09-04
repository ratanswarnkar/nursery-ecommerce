<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use App\Models\TaxClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true).' Plant';

        return [
            'brand_id' => Brand::factory(),
            'tax_class_id' => TaxClass::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'base_sku' => 'SKU-'.strtoupper(fake()->unique()->bothify('???-####')),
            'short_description' => fake()->sentence(),
            'full_description' => fake()->paragraphs(3, true),
            'is_active' => true,
            'is_featured' => false,
            'custom_attributes' => ['care_level' => 'easy', 'light_requirement' => 'indirect_sunlight'],
        ];
    }
}
