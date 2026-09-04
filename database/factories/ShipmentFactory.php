<?php

namespace Database\Factories;

use App\Enums\ShippingStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'tracking_number' => 'TRK'.fake()->unique()->numerify('##########'),
            'carrier' => 'BlueDart Express',
            'shipping_status' => ShippingStatus::UNFULFILLED,
            'shipped_at' => null,
            'delivered_at' => null,
            'notes' => 'Handle with care: live plants',
            'items_snapshot' => null,
        ];
    }
}
