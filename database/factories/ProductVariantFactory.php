<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 99, 2999);

        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-'.strtoupper(fake()->unique()->bothify('????-#####')),
            'barcode' => fake()->ean13(),
            'price' => $price,
            'compare_at_price' => $price + 100,
            'cost_price' => $price * 0.6,
            'weight' => fake()->randomFloat(2, 0.2, 10.0),
            'length' => 15.00,
            'width' => 15.00,
            'height' => 30.00,
            'is_active' => true,
            'is_default' => false,
            'custom_attributes' => ['pot_size' => '6 inch', 'color' => 'Terracotta'],
        ];
    }
}
