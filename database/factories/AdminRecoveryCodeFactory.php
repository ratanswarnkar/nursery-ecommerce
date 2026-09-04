<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\AdminRecoveryCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminRecoveryCodeFactory extends Factory
{
    protected $model = AdminRecoveryCode::class;

    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'code_hash' => Hash::make(Str::upper(Str::random(8)).'-'.Str::upper(Str::random(8))),
            'used_at' => null,
        ];
    }
}
