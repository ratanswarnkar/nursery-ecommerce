<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = 999.00;
        $tax = 179.82;
        $shipping = 50.00;
        $discount = 0.00;
        $grandTotal = $subtotal + $tax + $shipping - $discount;

        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.fake()->unique()->randomNumber(5),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'shipping_status' => ShippingStatus::UNFULFILLED,
            'currency' => 'INR',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'discount_amount' => $discount,
            'grand_total' => $grandTotal,
            'shipping_address_json' => [
                'recipient_name' => fake()->name(),
                'phone' => fake()->numerify('9#########'),
                'address_line_1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => 'Delhi',
                'postal_code' => '110001',
                'country' => 'India',
            ],
            'billing_address_json' => [
                'recipient_name' => fake()->name(),
                'phone' => fake()->numerify('9#########'),
                'address_line_1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => 'Delhi',
                'postal_code' => '110001',
                'country' => 'India',
            ],
            'coupon_id' => null,
            'coupon_code' => null,
            'notes' => fake()->sentence(),
        ];
    }
}
