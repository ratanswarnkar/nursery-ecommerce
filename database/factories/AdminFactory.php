<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'adminpassword123',
            'is_active' => true,
            'auth_token_version' => 1,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'recovery_codes' => null,
        ];
    }
}
