<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderReturnFactory extends Factory
{
    protected $model = OrderReturn::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => 1,
            'reason' => 'Plant arrived with minor leaf damage',
            'status' => 'requested',
            'refund_amount' => 499.00,
            'received_at' => null,
        ];
    }
}
