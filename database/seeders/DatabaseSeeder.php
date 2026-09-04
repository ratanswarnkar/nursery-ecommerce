<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\TaxRule;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $taxClass = TaxClass::firstOrCreate(
            ['name' => 'Standard GST 18%'],
            [
                'description' => 'Standard Goods and Services Tax 18%',
                'is_active' => true,
            ]
        );

        $taxRate = TaxRate::firstOrCreate(
            ['name' => 'GST 18%'],
            [
                'rate' => 18.00,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'effective_to' => null,
            ]
        );

        TaxRule::firstOrCreate(
            [
                'tax_class_id' => $taxClass->id,
                'tax_rate_id' => $taxRate->id,
            ],
            [
                'country' => 'IN',
                'state' => null,
                'postal_code' => null,
                'priority' => 1,
                'is_active' => true,
            ]
        );

        Warehouse::firstOrCreate(
            ['code' => 'MAIN-WH-01'],
            [
                'name' => 'Main Central Warehouse',
                'address_line_1' => 'Plot 101, Green Nursery Zone',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'postal_code' => '110001',
                'country' => 'India',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        $this->call([
            AdminRbacSeeder::class,
            DevelopmentAdminSeeder::class,
        ]);
    }
}
