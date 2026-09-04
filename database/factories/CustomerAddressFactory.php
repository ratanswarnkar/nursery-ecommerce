<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'address_type' => fake()->randomElement(AddressType::cases()),
            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('9#########'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => 'India',
            'is_default' => false,
        ];
    }
}
