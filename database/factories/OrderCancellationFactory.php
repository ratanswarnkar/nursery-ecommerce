<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderCancellation;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderCancellationFactory extends Factory
{
    protected $model = OrderCancellation::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'reason' => 'Customer requested cancellation prior to dispatch',
            'status' => 'pending',
            'requested_by_type' => null,
            'requested_by_id' => null,
            'approved_by_id' => null,
            'approved_at' => null,
        ];
    }
}
