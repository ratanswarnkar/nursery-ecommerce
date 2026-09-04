<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'warehouse_id' => Warehouse::factory(),
            'type' => StockMovementType::INBOUND,
            'quantity' => 50,
            'previous_quantity' => 0,
            'new_quantity' => 50,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => 'Initial stock intake',
            'created_by' => null,
        ];
    }
}
