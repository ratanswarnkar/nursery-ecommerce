<?php

namespace Database\Factories;

use App\Models\PaymentWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentWebhookFactory extends Factory
{
    protected $model = PaymentWebhook::class;

    public function definition(): array
    {
        return [
            'gateway' => 'razorpay',
            'event_id' => 'evt_'.fake()->unique()->bothify('??????????????'),
            'event_type' => 'payment.captured',
            'payload' => ['event' => 'payment.captured', 'entity' => 'event'],
            'is_processed' => false,
            'processed_at' => null,
        ];
    }
}
