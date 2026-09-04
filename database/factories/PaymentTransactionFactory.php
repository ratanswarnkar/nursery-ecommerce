<?php

namespace Database\Factories;

use App\Enums\PaymentTransactionStatus;
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
            'transaction_number' => 'TXN-'.fake()->unique()->numerify('##########'),
            'gateway' => 'razorpay',
            'gateway_transaction_id' => 'pay_'.fake()->unique()->bothify('??????????????'),
            'amount' => 1228.82,
            'currency' => 'INR',
            'status' => PaymentTransactionStatus::CREATED,
            'payment_method' => 'upi',
            'payload' => null,
        ];
    }
}
