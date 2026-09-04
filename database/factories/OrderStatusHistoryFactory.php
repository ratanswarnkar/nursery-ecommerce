<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'from_status' => OrderStatus::PENDING,
            'to_status' => OrderStatus::CONFIRMED,
            'comment' => 'Order verified and confirmed',
            'changed_by_type' => null,
            'changed_by_id' => null,
        ];
    }
}
