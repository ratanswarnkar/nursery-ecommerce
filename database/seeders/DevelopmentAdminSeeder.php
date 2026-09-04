<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('DEV_ADMIN_EMAIL', 'admin@nurseryecommerce.local');
        $password = env('DEV_ADMIN_PASSWORD', 'AdminSecureDevPass!2026');

        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'System Super Admin',
                'password' => Hash::make($password),
                'is_active' => true,
                'auth_token_version' => 1,
            ]
        );

        $admin->syncRoles(['Super Admin']);
    }
}
