<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'category' => 'Plant Care',
            'question' => 'How often should I water my indoor plants?',
            'answer' => 'Water when the top 1-2 inches of soil feel dry to the touch.',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
