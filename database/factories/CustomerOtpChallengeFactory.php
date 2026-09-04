<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerOtpChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerOtpChallengeFactory extends Factory
{
    protected $model = CustomerOtpChallenge::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'phone_e164' => '+91'.fake()->numerify('9#########'),
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'verified_at' => null,
            'attempts' => 0,
            'resend_available_at' => now()->addSeconds(60),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestBrowser/1.0',
        ];
    }
}
