<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Seed the 9 officially approved product categories.
     * Idempotently creates or updates each category without introducing duplicates.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Indoor Plants',
                'slug' => 'indoor-plants',
                'description' => 'Flourishing low-maintenance houseplants suited for living rooms, bedrooms, and workspaces.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Flowering Plants',
                'slug' => 'flowering-plants',
                'description' => 'Vibrant seasonal and perennial blooms that bring natural color, life, and fragrance.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Air Purifying Plants',
                'slug' => 'air-purifying',
                'description' => 'NASA-recommended foliage specimens that naturally filter airborne toxins and refresh indoor air.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Fruit Plants',
                'slug' => 'fruit-plants',
                'description' => 'Healthy fruiting trees and saplings ideal for home orchards, sunny terraces, and garden beds.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Outdoor Plants',
                'slug' => 'outdoor-plants',
                'description' => 'Hardy, sun-loving ornamental hedges, shrubs, and border plants suited for Delhi NCR weather.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Herbal & Medicinal Plants',
                'slug' => 'herbal-medicinal-plants',
                'description' => 'Therapeutic and culinary botanicals including sacred Tulsi, Aloe Vera, Lemongrass, and Mint.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Flowering Saplings',
                'slug' => 'flowering-saplings',
                'description' => 'Nurtured young saplings ready for transplanting into garden beds, borders, and decorative planters.',
                'sort_order' => 7,
            ],
            [
                'name' => 'Terracotta Pots',
                'slug' => 'terracotta-pots',
                'description' => 'Artisanal high-fired porous clay pottery that keeps soil aerated and roots naturally cool.',
                'sort_order' => 8,
            ],
            [
                'name' => 'Plant Care / Potting Mix',
                'slug' => 'plant-care',
                'description' => 'Enriched organic potting soils, slow-release bio-fertilizers, neem cake, and peat-free compost blends.',
                'sort_order' => 9,
            ],
        ];

        foreach ($categories as $catData) {
            Category::updateOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'description' => $catData['description'],
                    'sort_order' => $catData['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
