<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => 'SAVE'.fake()->unique()->numerify('####'),
            'type' => CouponType::PERCENTAGE,
            'value' => 10.00,
            'min_spend' => 500.00,
            'max_discount' => 200.00,
            'usage_limit_total' => 100,
            'usage_limit_per_customer' => 1,
            'total_used' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ];
    }
}
