<?php

namespace Database\Factories;

use App\Enums\StockReservationStatus;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockReservationFactory extends Factory
{
    protected $model = StockReservation::class;

    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => 2,
            'status' => StockReservationStatus::ACTIVE,
            'reference_type' => null,
            'reference_id' => null,
            'expires_at' => now()->addMinutes(30),
        ];
    }
}
