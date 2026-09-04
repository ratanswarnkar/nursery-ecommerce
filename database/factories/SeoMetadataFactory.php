<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeoMetadataFactory extends Factory
{
    protected $model = SeoMetadata::class;

    public function definition(): array
    {
        return [
            'seoable_type' => Product::class,
            'seoable_id' => Product::factory(),
            'meta_title' => 'Buy Online | Best Quality Nursery Plants',
            'meta_description' => 'Explore a wide range of exotic indoor and outdoor plants delivered fresh.',
            'meta_keywords' => 'plants, nursery, indoor plants, gardening',
            'canonical_url' => fake()->url(),
            'og_title' => 'Nursery Online Plant Store',
            'og_description' => 'Fresh indoor plants directly from our nursery.',
            'og_image' => 'https://example.com/images/og-plant.jpg',
            'json_ld' => ['@context' => 'https://schema.org', '@type' => 'Product'],
        ];
    }
}
