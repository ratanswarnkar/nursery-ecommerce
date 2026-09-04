<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\AdminLoginActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminLoginActivityFactory extends Factory
{
    protected $model = AdminLoginActivity::class;

    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
            'login_at' => now(),
            'is_successful' => true,
            'failure_reason' => null,
        ];
    }
}
