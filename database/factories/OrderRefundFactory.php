<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderRefundFactory extends Factory
{
    protected $model = OrderRefund::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_transaction_id' => null,
            'amount' => 499.00,
            'reason' => 'Customer return approved and processed',
            'status' => 'pending',
            'refund_reference' => 'REF-'.fake()->unique()->numerify('########'),
            'processed_at' => null,
        ];
    }
}
