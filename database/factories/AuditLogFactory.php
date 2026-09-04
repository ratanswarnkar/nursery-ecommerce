<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'action' => 'updated_tender_rate',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => ['rate' => 200.00],
            'new_values' => ['rate' => 220.00],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
        ];
    }
}
