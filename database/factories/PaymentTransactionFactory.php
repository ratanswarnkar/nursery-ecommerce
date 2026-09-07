<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'transaction_number' => 'PAY-'.fake()->unique()->numerify('##########'),
            'gateway' => 'null',
            'gateway_transaction_id' => 'null_txn_'.fake()->unique()->bothify('??????????????'),
            'amount' => 1228.82,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'payment_method' => 'simulated',
            'payload' => null,
        ];
    }
}
