<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price = 499.00;
        $qty = 2;
        $subtotal = $price * $qty;
        $tax = $subtotal * 0.18;
        $discount = 0.00;
        $total = $subtotal + $tax - $discount;

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => 'Fiddle Leaf Fig',
            'variant_name' => 'Large - Ceramic Pot',
            'sku' => 'FID-FIG-LG',
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total' => $total,
            'metadata' => null,
        ];
    }
}
