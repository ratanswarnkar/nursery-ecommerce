<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'order_id' => Order::factory(),
            'rating' => 5,
            'title' => 'Healthy and vibrant plant!',
            'comment' => 'Received the plant in pristine condition. Packaging was wonderful.',
            'status' => ReviewStatus::PENDING,
            'moderated_by_id' => null,
            'moderated_at' => null,
        ];
    }
}
