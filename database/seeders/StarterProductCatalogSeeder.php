<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SeoMetadata;
use App\Models\TaxClass;
use App\Models\Warehouse;
use App\Services\Catalog\BulkProductImageImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StarterProductCatalogSeeder extends Seeder
{
    /**
     * Seed starter nursery products idempotently across all 9 approved categories.
     */
    public function run(): void
    {
        // 0. Ensure Storage Directory and Assets
        $storageCatalogDir = storage_path('app/public/products/catalog');
        if (! File::isDirectory($storageCatalogDir)) {
            File::makeDirectory($storageCatalogDir, 0755, true);
        }

        $sourceImagesDir = resource_path('images/catalog');
        if (File::isDirectory($sourceImagesDir)) {
            foreach (File::files($sourceImagesDir) as $imgFile) {
                $targetFile = $storageCatalogDir.'/'.$imgFile->getFilename();
                if (! File::exists($targetFile)) {
                    File::copy($imgFile->getPathname(), $targetFile);
                }
            }
        }
        // 1. Tax Class
        $taxClass = TaxClass::firstOrCreate(
            ['name' => 'Standard GST 18%'],
            ['description' => 'Standard Goods and Services Tax 18%', 'is_active' => true]
        );

        // 2. Central Warehouse
        $warehouse = Warehouse::firstOrCreate(
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

        // 3. Brands
        $brands = [
            'verdant-living' => Brand::firstOrCreate(
                ['slug' => 'verdant-living'],
                ['name' => 'Verdant Living', 'description' => 'Acclimatized nursery plants nurtured in organic conditions.', 'is_active' => true]
            ),
            'terra-haven' => Brand::firstOrCreate(
                ['slug' => 'terra-haven'],
                ['name' => 'Terra Haven', 'description' => 'Air-purifying botanical varieties propagated for homes and offices.', 'is_active' => true]
            ),
            'leafcraft-pottery' => Brand::firstOrCreate(
                ['slug' => 'leafcraft-pottery'],
                ['name' => 'LeafCraft Pottery', 'description' => 'Artisanal earthenware and breathable terracotta planters.', 'is_active' => true]
            ),
            'bioflora-organics' => Brand::firstOrCreate(
                ['slug' => 'bioflora-organics'],
                ['name' => 'BioFlora Organics', 'description' => 'Natural organic composts, potting substrates, and bio-nutrients.', 'is_active' => true]
            ),
        ];

        // 4. Products Definition (45 unique products across 9 categories)
        $catalog = $this->getCatalogDefinitions();

        foreach ($catalog as $item) {
            $category = Category::where('slug', $item['category_slug'])->first();
            if (! $category) {
                continue;
            }

            $brand = $brands[$item['brand_key']] ?? null;

            // Product (idempotent updateOrCreate by slug)
            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'brand_id' => $brand?->id,
                    'tax_class_id' => $taxClass->id,
                    'name' => $item['name'],
                    'base_sku' => $item['sku'],
                    'short_description' => $item['short_description'],
                    'full_description' => $item['full_description'],
                    'is_active' => true,
                    'is_featured' => $item['is_featured'] ?? false,
                    'custom_attributes' => $item['custom_attributes'] ?? null,
                ]
            );

            // Category Sync (Primary category)
            $product->categories()->syncWithoutDetaching([
                $category->id => ['is_primary' => true],
            ]);

            // Default Variant (idempotent updateOrCreate by sku)
            $variant = ProductVariant::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'product_id' => $product->id,
                    'price' => $item['price'],
                    'compare_at_price' => $item['compare_at_price'] ?? null,
                    'cost_price' => $item['cost_price'] ?? null,
                    'is_active' => true,
                    'is_default' => true,
                ]
            );

            // Inventory (idempotent updateOrCreate by variant + warehouse)
            Inventory::updateOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity' => $item['stock'],
                    'reserved_quantity' => 0,
                    'safety_stock' => $item['safety_stock'] ?? 3,
                ]
            );

            // SEO Metadata (idempotent updateOrCreate)
            SeoMetadata::updateOrCreate(
                [
                    'seoable_type' => Product::class,
                    'seoable_id' => $product->id,
                ],
                [
                    'meta_title' => $item['name'].' | Sugandha Farms and Nursery',
                    'meta_description' => $item['short_description'],
                    'canonical_url' => route('products.show', $product->slug),
                ]
            );

            // Associate local image if file exists
            $imageFileName = $item['slug'].'.jpg';
            $imageFilePath = 'products/catalog/'.$imageFileName;
            $fullStoragePath = storage_path('app/public/'.$imageFilePath);

            if (File::exists($fullStoragePath)) {
                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'file_path' => $imageFilePath,
                    ],
                    [
                        'alt_text' => "Lush {$product->name} nursery specimen",
                        'sort_order' => 0,
                        'is_primary' => true,
                    ]
                );
            }
        }

        // 5. Run bulk image importer to link any additional matching files
        app(BulkProductImageImporter::class)->importFromDirectory();
    }

    /**
     * Get definitions for all 45 unique starter products.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCatalogDefinitions(): array
    {
        return [
            // ==========================================
            // 1. INDOOR PLANTS (5 products)
            // ==========================================
            [
                'name' => 'Money Plant Golden Pothos',
                'slug' => 'money-plant-golden-pothos',
                'sku' => 'PLN-IND-01',
                'category_slug' => 'indoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 199.00,
                'compare_at_price' => 249.00,
                'cost_price' => 90.00,
                'stock' => 35,
                'is_featured' => true,
                'short_description' => 'Iconic heart-leaf climbing vine with golden-yellow variegation, thriving in indoor shade.',
                'full_description' => 'The Golden Pothos (Money Plant) is renowned across Indian homes for its hardy adaptability. Grows beautifully in water bottles or well-draining soil, purifying indoor air while tolerating irregular watering.',
                'custom_attributes' => [
                    'botanical_name' => 'Epipremnum aureum',
                    'light_requirement' => 'Low to Bright Indirect Light',
                    'watering' => 'Once every 5-7 days',
                    'suitable_location' => 'Living Rooms / Office Desks',
                    'plant_height' => '8 - 12 inches',
                    'maintenance_level' => 'Easy / Beginner',
                ],
            ],
            [
                'name' => 'ZZ Plant (Zamioculcas Zamiifolia)',
                'slug' => 'zz-plant-zamioculcas',
                'sku' => 'PLN-IND-02',
                'category_slug' => 'indoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 399.00,
                'compare_at_price' => 499.00,
                'cost_price' => 180.00,
                'stock' => 25,
                'is_featured' => true,
                'short_description' => 'Nearly indestructible indoor houseplant featuring glossy, deep green architectural foliage.',
                'full_description' => 'Zamioculcas Zamiifolia, or ZZ Plant, thrives in low-light environments and handles weeks of drought thanks to moisture-storing rhizomes. Ideal for bedrooms, dim corners, and air-conditioned offices.',
                'custom_attributes' => [
                    'botanical_name' => 'Zamioculcas zamiifolia',
                    'light_requirement' => 'Low Light to Shade',
                    'watering' => 'Once every 10-14 days',
                    'suitable_location' => 'Bedrooms / Dim Hallways',
                    'plant_height' => '12 - 16 inches',
                    'maintenance_level' => 'Very Low Maintenance',
                ],
            ],
            [
                'name' => 'Areca Palm Indoor',
                'slug' => 'areca-palm-indoor',
                'sku' => 'PLN-IND-03',
                'category_slug' => 'indoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 449.00,
                'compare_at_price' => 549.00,
                'cost_price' => 220.00,
                'stock' => 20,
                'is_featured' => false,
                'short_description' => 'Lush tropical indoor palm with delicate arching green fronds that humidify dry air.',
                'full_description' => 'Areca Palm brings lush natural elegance to spacious rooms. Its fine feathery fronds act as an organic humidifier and remove household airborne pollutants.',
                'custom_attributes' => [
                    'botanical_name' => 'Dypsis lutescens',
                    'light_requirement' => 'Bright Filtered Sunlight',
                    'watering' => 'Twice weekly',
                    'suitable_location' => 'Living Rooms / Shaded Balconies',
                    'plant_height' => '24 - 30 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Boston Fern',
                'slug' => 'boston-fern-indoor',
                'sku' => 'PLN-IND-04',
                'category_slug' => 'indoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 299.00,
                'compare_at_price' => 349.00,
                'cost_price' => 130.00,
                'stock' => 22,
                'is_featured' => false,
                'short_description' => 'Classic trailing fern with lush, feather-like sword fronds that adore ambient humidity.',
                'full_description' => 'Nephrolepis exaltata (Boston Fern) produces lush weeping foliage perfect for indoor plant stands and hanging planters. Thrives when misted regularly.',
                'custom_attributes' => [
                    'botanical_name' => 'Nephrolepis exaltata',
                    'light_requirement' => 'Filtered Morning Sunlight / Bright Shade',
                    'watering' => 'Keep soil consistently moist',
                    'suitable_location' => 'Bathrooms / Shaded Patios',
                    'plant_height' => '10 - 14 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Aglaonema Pink Anjamani',
                'slug' => 'aglaonema-pink-indoor',
                'sku' => 'PLN-IND-05',
                'category_slug' => 'indoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 349.00,
                'compare_at_price' => 429.00,
                'cost_price' => 160.00,
                'stock' => 30,
                'is_featured' => false,
                'short_description' => 'Stunning Chinese Evergreen variety showcasing vibrant pink and green patterned foliage.',
                'full_description' => 'Aglaonema Pink Anjamani provides a burst of exotic tropical color indoors without demanding direct sun. Remarkably forgiving and long-lasting in interior decor.',
                'custom_attributes' => [
                    'botanical_name' => 'Aglaonema commutatum',
                    'light_requirement' => 'Moderate to Low Indirect Light',
                    'watering' => 'Once weekly',
                    'suitable_location' => 'Coffee Tables / Living Rooms',
                    'plant_height' => '8 - 12 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],

            // ==========================================
            // 2. FLOWERING PLANTS (5 products)
            // ==========================================
            [
                'name' => 'Desi Red Rose Plant',
                'slug' => 'desi-red-rose-plant',
                'sku' => 'PLN-FLW-01',
                'category_slug' => 'flowering-plants',
                'brand_key' => 'verdant-living',
                'price' => 179.00,
                'compare_at_price' => 219.00,
                'cost_price' => 80.00,
                'stock' => 40,
                'is_featured' => true,
                'short_description' => 'Intensely fragrant indigenous Indian red rose, blooming abundantly in sunny outdoor spots.',
                'full_description' => 'Traditional Desi Gulab prized for its intoxicating natural fragrance and continuous flowering cycles. Highly resilient to local climate conditions when given direct sunlight.',
                'custom_attributes' => [
                    'botanical_name' => 'Rosa indica',
                    'light_requirement' => 'Full Direct Sunlight (5-6 hours)',
                    'watering' => 'Daily in summer / alternate days in winter',
                    'suitable_location' => 'Sunny Balconies / Terrace Gardens',
                    'plant_height' => '14 - 18 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Red Hibiscus Plant (Gudhal)',
                'slug' => 'red-hibiscus-plant',
                'sku' => 'PLN-FLW-02',
                'category_slug' => 'flowering-plants',
                'brand_key' => 'verdant-living',
                'price' => 149.00,
                'compare_at_price' => 189.00,
                'cost_price' => 65.00,
                'stock' => 35,
                'is_featured' => false,
                'short_description' => 'Perennial tropical flowering shrub with bold crimson petals and attractive yellow pollen stamen.',
                'full_description' => 'Hibiscus rosa-sinensis (Gudhal) is sacred, ornamental, and continuous-blooming. Thrives in Indian weather, attracting butterflies and beneficial pollinators to your garden.',
                'custom_attributes' => [
                    'botanical_name' => 'Hibiscus rosa-sinensis',
                    'light_requirement' => 'Full Sunlight',
                    'watering' => 'Regularly when topsoil dries',
                    'suitable_location' => 'Gardens / Balconies',
                    'plant_height' => '15 - 20 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Bougainvillea Glabra',
                'slug' => 'bougainvillea-flowering-plant',
                'sku' => 'PLN-FLW-03',
                'category_slug' => 'flowering-plants',
                'brand_key' => 'verdant-living',
                'price' => 199.00,
                'compare_at_price' => 249.00,
                'cost_price' => 85.00,
                'stock' => 28,
                'is_featured' => false,
                'short_description' => 'Drought-hardy ornamental climber blanketed in vivid magenta-purple paper flower bracts.',
                'full_description' => 'Bougainvillea is an iconic sun-loving ornamental for boundaries, fences, and terrace railings. Requires minimal water once established and delivers dramatic blooming flushes.',
                'custom_attributes' => [
                    'botanical_name' => 'Bougainvillea glabra',
                    'light_requirement' => 'Intense Direct Sunlight',
                    'watering' => 'Low / Drought Tolerant',
                    'suitable_location' => 'Boundary Walls / Terraces',
                    'plant_height' => '18 - 24 inches',
                    'maintenance_level' => 'Very Low Maintenance',
                ],
            ],
            [
                'name' => 'Mogra Arabian Jasmine',
                'slug' => 'mogra-jasmine-plant',
                'sku' => 'PLN-FLW-04',
                'category_slug' => 'flowering-plants',
                'brand_key' => 'verdant-living',
                'price' => 169.00,
                'compare_at_price' => 209.00,
                'cost_price' => 75.00,
                'stock' => 45,
                'is_featured' => true,
                'short_description' => 'Beloved fragrant white jasmine producing intensely aromatic sweet blooms through spring and summer.',
                'full_description' => 'Jasminum sambac (Mogra) is cherished across India for its celestial perfume and cultural significance. Produces clusters of pure white star blooms from March through October.',
                'custom_attributes' => [
                    'botanical_name' => 'Jasminum sambac',
                    'light_requirement' => 'Bright Sunlight',
                    'watering' => 'Moderate, keep well-drained',
                    'suitable_location' => 'Outdoor Planters / Courtyards',
                    'plant_height' => '12 - 16 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'African Orange Marigold',
                'slug' => 'orange-marigold-plant',
                'sku' => 'PLN-FLW-05',
                'category_slug' => 'flowering-plants',
                'brand_key' => 'verdant-living',
                'price' => 89.00,
                'compare_at_price' => 119.00,
                'cost_price' => 35.00,
                'stock' => 50,
                'is_featured' => false,
                'short_description' => 'Cheerful ruffled golden-orange flower heads that brighten borders and repel garden pests naturally.',
                'full_description' => 'Tagetes erecta (Genda) produces vibrant pom-pom blossoms with natural insect-repelling properties. Fast growing, pest-resistant, and perfect for festival decoration.',
                'custom_attributes' => [
                    'botanical_name' => 'Tagetes erecta',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Regular moderate watering',
                    'suitable_location' => 'Balcony Planters / Flower Beds',
                    'plant_height' => '10 - 14 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],

            // ==========================================
            // 3. AIR PURIFYING PLANTS (5 products)
            // ==========================================
            [
                'name' => 'Sansevieria Snake Plant',
                'slug' => 'sansevieria-snake-plant',
                'sku' => 'PLN-AIR-01',
                'category_slug' => 'air-purifying',
                'brand_key' => 'terra-haven',
                'price' => 299.00,
                'compare_at_price' => 379.00,
                'cost_price' => 120.00,
                'stock' => 40,
                'is_featured' => true,
                'short_description' => 'Top NASA-rated air-purifying architectural succulent releasing oxygen during nighttime hours.',
                'full_description' => "Sansevieria trifasciata (Mother-in-Law's Tongue) removes toxins like formaldehyde, xylene, and benzene. Releases oxygen at night, making it the ideal bedside bedroom plant.",
                'custom_attributes' => [
                    'botanical_name' => 'Sansevieria trifasciata',
                    'light_requirement' => 'Any Light (Low to Full Sun)',
                    'watering' => 'Once every 10-14 days',
                    'suitable_location' => 'Bedrooms / Workstations',
                    'plant_height' => '14 - 18 inches',
                    'maintenance_level' => 'Very Easy',
                ],
            ],
            [
                'name' => 'Variegated Spider Plant',
                'slug' => 'variegated-spider-plant',
                'sku' => 'PLN-AIR-02',
                'category_slug' => 'air-purifying',
                'brand_key' => 'terra-haven',
                'price' => 189.00,
                'compare_at_price' => 229.00,
                'cost_price' => 80.00,
                'stock' => 30,
                'is_featured' => false,
                'short_description' => 'Non-toxic, pet-safe air purifier with arching green and creamy white ribbon leaves.',
                'full_description' => 'Chlorophytum comosum cleans carbon monoxide and formaldehyde from indoor air. Completely non-toxic for cats and dogs, producing cute baby spider plantlets on runners.',
                'custom_attributes' => [
                    'botanical_name' => 'Chlorophytum comosum',
                    'light_requirement' => 'Bright Indirect Sunlight',
                    'watering' => 'Once or twice a week',
                    'suitable_location' => 'Hanging Baskets / Windowsills',
                    'plant_height' => '8 - 12 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Peace Lily Spathiphyllum',
                'slug' => 'peace-lily-spathiphyllum',
                'sku' => 'PLN-AIR-03',
                'category_slug' => 'air-purifying',
                'brand_key' => 'terra-haven',
                'price' => 349.00,
                'compare_at_price' => 429.00,
                'cost_price' => 150.00,
                'stock' => 25,
                'is_featured' => true,
                'short_description' => 'Elegant air purifier breaking down indoor volatile organic compounds with striking white blooms.',
                'full_description' => 'Spathiphyllum is one of the most effective natural indoor filters for acetone, trichloroethylene, and benzene. Shows gentle leaf droop when thirsty, bouncing back within hours of watering.',
                'custom_attributes' => [
                    'botanical_name' => 'Spathiphyllum wallisii',
                    'light_requirement' => 'Low to Medium Indirect Light',
                    'watering' => 'Once every 4-6 days',
                    'suitable_location' => 'Indoor Living Rooms / Office Lounges',
                    'plant_height' => '12 - 16 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Burgundy Rubber Plant',
                'slug' => 'burgundy-rubber-plant',
                'sku' => 'PLN-AIR-04',
                'category_slug' => 'air-purifying',
                'brand_key' => 'terra-haven',
                'price' => 499.00,
                'compare_at_price' => 599.00,
                'cost_price' => 210.00,
                'stock' => 18,
                'is_featured' => false,
                'short_description' => 'Broad leather-like deep glossy leaves that trap airborne dust and absorb indoor toxins.',
                'full_description' => "Ficus elastica 'Burgundy' boasts thick, sculptural dark purple-green leaves. Its large leaf surface area makes it an exceptional dust and toxin scavenger for urban homes.",
                'custom_attributes' => [
                    'botanical_name' => 'Ficus elastica',
                    'light_requirement' => 'Bright Indirect to Gentle Morning Sun',
                    'watering' => 'Once a week',
                    'suitable_location' => 'Living Room Corners',
                    'plant_height' => '18 - 24 inches',
                    'maintenance_level' => 'Easy to Moderate',
                ],
            ],
            [
                'name' => 'English Ivy Plant',
                'slug' => 'english-ivy-plant',
                'sku' => 'PLN-AIR-05',
                'category_slug' => 'air-purifying',
                'brand_key' => 'terra-haven',
                'price' => 249.00,
                'compare_at_price' => 299.00,
                'cost_price' => 100.00,
                'stock' => 20,
                'is_featured' => false,
                'short_description' => 'Trailing evergreen vine proven to reduce airborne mold particles and volatile toxins.',
                'full_description' => 'Hedera helix (English Ivy) filters airborne mold particles and benzene. Looks gorgeous cascading from high shelves, hanging macramé baskets, or trained along moss poles.',
                'custom_attributes' => [
                    'botanical_name' => 'Hedera helix',
                    'light_requirement' => 'Bright Filtered Light',
                    'watering' => 'Allow top inch to dry between waterings',
                    'suitable_location' => 'Bookshelves / Shaded Windows',
                    'plant_height' => '8 - 12 inches (trailing)',
                    'maintenance_level' => 'Moderate',
                ],
            ],

            // ==========================================
            // 4. FRUIT PLANTS (5 products)
            // ==========================================
            [
                'name' => 'Kagzi Lemon Plant',
                'slug' => 'kagzi-lemon-plant',
                'sku' => 'PLN-FRT-01',
                'category_slug' => 'fruit-plants',
                'brand_key' => 'verdant-living',
                'price' => 229.00,
                'compare_at_price' => 279.00,
                'cost_price' => 95.00,
                'stock' => 25,
                'is_featured' => true,
                'short_description' => 'Thin-skinned, highly juicy Indian Kagzi Nimbu variety suitable for containers and garden beds.',
                'full_description' => 'Citrus aurantifolia (Kagzi Lemon) yields continuous crops of aromatic, thin-skinned lemons rich in Vitamin C. Acclimatized for rooftop pots and kitchen gardens.',
                'custom_attributes' => [
                    'botanical_name' => 'Citrus aurantifolia',
                    'light_requirement' => 'Full Direct Sun (6+ hours)',
                    'watering' => 'Deep watering when topsoil dries',
                    'suitable_location' => 'Terrace / Rooftop Garden',
                    'plant_height' => '18 - 24 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Allahabad Safeda Guava',
                'slug' => 'allahabad-guava-plant',
                'sku' => 'PLN-FRT-02',
                'category_slug' => 'fruit-plants',
                'brand_key' => 'verdant-living',
                'price' => 249.00,
                'compare_at_price' => 299.00,
                'cost_price' => 110.00,
                'stock' => 20,
                'is_featured' => false,
                'short_description' => 'Famous Indian guava cultivar yielding sweet, white-fleshed fruit with pleasant aroma.',
                'full_description' => "Psidium guajava 'Allahabad Safeda' is celebrated across Northern India for its heavy fruiting, sweet fragrant flesh, and hardy disease resistance in subtropical climates.",
                'custom_attributes' => [
                    'botanical_name' => 'Psidium guajava',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Moderate',
                    'suitable_location' => 'Outdoor Soil / Large Container',
                    'plant_height' => '24 - 30 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Bhagwa Red Pomegranate',
                'slug' => 'bhagwa-pomegranate-plant',
                'sku' => 'PLN-FRT-03',
                'category_slug' => 'fruit-plants',
                'brand_key' => 'verdant-living',
                'price' => 279.00,
                'compare_at_price' => 349.00,
                'cost_price' => 120.00,
                'stock' => 22,
                'is_featured' => false,
                'short_description' => 'High-yield commercial cultivar producing deep crimson, soft-seeded sweet pomegranates.',
                'full_description' => "Punica granatum 'Bhagwa' is India's most popular pomegranate variety. Compact growth habit makes it well-suited for large patio containers and sunny courtyards.",
                'custom_attributes' => [
                    'botanical_name' => 'Punica granatum',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Low to Moderate, drought tolerant',
                    'suitable_location' => 'Sunny Terrace / Farm Garden',
                    'plant_height' => '20 - 26 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'All-Time Mango Plant',
                'slug' => 'all-time-mango-plant',
                'sku' => 'PLN-FRT-04',
                'category_slug' => 'fruit-plants',
                'brand_key' => 'verdant-living',
                'price' => 499.00,
                'compare_at_price' => 599.00,
                'cost_price' => 240.00,
                'stock' => 15,
                'is_featured' => true,
                'short_description' => 'Grafted dwarf mango variety capable of flowering and bearing fruit multiple times a year.',
                'full_description' => "Mangifera indica 'Baramasi' (All-Time Mango) is a grafted dwarf variety that produces flowers and delicious mangoes across multiple seasons. Excellent for terrace containers.",
                'custom_attributes' => [
                    'botanical_name' => 'Mangifera indica',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Deep regular watering, avoid waterlogging',
                    'suitable_location' => 'Sunny Open Terrace / Ground',
                    'plant_height' => '24 - 32 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Red Lady Taiwan Papaya',
                'slug' => 'red-lady-papaya-plant',
                'sku' => 'PLN-FRT-05',
                'category_slug' => 'fruit-plants',
                'brand_key' => 'verdant-living',
                'price' => 159.00,
                'compare_at_price' => 199.00,
                'cost_price' => 60.00,
                'stock' => 28,
                'is_featured' => false,
                'short_description' => 'Early-bearing dwarf papaya hybrid producing sweet, deep-orange aromatic fruit.',
                'full_description' => "Carica papaya 'Red Lady 786' begins flowering and setting fruit at low heights within 8-9 months. Excellent high-yield fruit starter for kitchen gardens.",
                'custom_attributes' => [
                    'botanical_name' => 'Carica papaya',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Regular but well-drained',
                    'suitable_location' => 'Garden Bed / Large 50L Drum',
                    'plant_height' => '16 - 22 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],

            // ==========================================
            // 5. OUTDOOR PLANTS (5 products)
            // ==========================================
            [
                'name' => 'Golden Petra Croton',
                'slug' => 'golden-petra-croton',
                'sku' => 'PLN-OUT-01',
                'category_slug' => 'outdoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 219.00,
                'compare_at_price' => 269.00,
                'cost_price' => 95.00,
                'stock' => 30,
                'is_featured' => false,
                'short_description' => 'Spectacular multi-hued shrub with leathery leaves in shades of yellow, red, orange, and green.',
                'full_description' => "Codiaeum variegatum 'Petra' delivers bold tropical garden color. The more natural sunlight it receives, the more intense its fiery leaf coloration becomes.",
                'custom_attributes' => [
                    'botanical_name' => 'Codiaeum variegatum',
                    'light_requirement' => 'Bright Sunlight to Light Dappled Shade',
                    'watering' => 'Moderate',
                    'suitable_location' => 'Balconies / Garden Borders',
                    'plant_height' => '14 - 18 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Ficus Benjamina Weeping Fig',
                'slug' => 'ficus-benjamina-outdoor',
                'sku' => 'PLN-OUT-02',
                'category_slug' => 'outdoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 349.00,
                'compare_at_price' => 429.00,
                'cost_price' => 150.00,
                'stock' => 18,
                'is_featured' => false,
                'short_description' => 'Graceful weeping canopy tree with glossy pointed leaves, perfect for courtyards and patios.',
                'full_description' => 'Ficus benjamina is an enduring landscape favorite. Dense branching and elegant weeping form make it suitable for ornamental pruning, topiary, or shaded seating canopies.',
                'custom_attributes' => [
                    'botanical_name' => 'Ficus benjamina',
                    'light_requirement' => 'Bright Light to Partial Sunlight',
                    'watering' => 'Once top 2 inches dry out',
                    'suitable_location' => 'Courtyards / Verandahs',
                    'plant_height' => '24 - 30 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Golden Duranta Hedge',
                'slug' => 'golden-duranta-hedge',
                'sku' => 'PLN-OUT-03',
                'category_slug' => 'outdoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 119.00,
                'compare_at_price' => 149.00,
                'cost_price' => 45.00,
                'stock' => 50,
                'is_featured' => false,
                'short_description' => 'Vibrant lime-yellow foliage shrub, the premier choice for boundary hedges and landscape borders.',
                'full_description' => "Duranta erecta 'Gold Mound' provides bright chartreuse-yellow foliage year-round. Responds vigorously to shearing, making it the top choice for garden hedges in Delhi NCR.",
                'custom_attributes' => [
                    'botanical_name' => 'Duranta erecta',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Moderate',
                    'suitable_location' => 'Boundary Borders / Driveway Edges',
                    'plant_height' => '12 - 16 inches',
                    'maintenance_level' => 'Very Easy',
                ],
            ],
            [
                'name' => 'Dwarf Pink Ixora',
                'slug' => 'dwarf-pink-ixora',
                'sku' => 'PLN-OUT-04',
                'category_slug' => 'outdoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 169.00,
                'compare_at_price' => 209.00,
                'cost_price' => 70.00,
                'stock' => 32,
                'is_featured' => false,
                'short_description' => 'Dense evergreen shrub topped with compact, globe-shaped clusters of bright pink florets.',
                'full_description' => 'Ixora chinensis blooms almost continuously under Indian sun. Its compact mounding habit and glossy dark leaves provide lush structure in border plantings and patio containers.',
                'custom_attributes' => [
                    'botanical_name' => 'Ixora chinensis',
                    'light_requirement' => 'Full Sun to Partial Shade',
                    'watering' => 'Regular, acidic soil preferred',
                    'suitable_location' => 'Garden Beds / Patio Planters',
                    'plant_height' => '10 - 14 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Cycas Revoluta Sago Palm',
                'slug' => 'cycas-sago-palm',
                'sku' => 'PLN-OUT-05',
                'category_slug' => 'outdoor-plants',
                'brand_key' => 'verdant-living',
                'price' => 599.00,
                'compare_at_price' => 749.00,
                'cost_price' => 280.00,
                'stock' => 15,
                'is_featured' => true,
                'short_description' => 'Ancient architectural cycad with symmetrical crown of stiff, glossy dark green feather leaves.',
                'full_description' => 'Cycas revoluta (Sago Palm) is a timeless statement specimen for landscape entrances and formal garden urns. Extremely slow-growing, drought-hardy, and resilient.',
                'custom_attributes' => [
                    'botanical_name' => 'Cycas revoluta',
                    'light_requirement' => 'Full Sun to Semi-Shade',
                    'watering' => 'Low to Moderate, avoid overwatering',
                    'suitable_location' => 'Main Entrance / Villa Gardens',
                    'plant_height' => '16 - 22 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],

            // ==========================================
            // 6. HERBAL & MEDICINAL PLANTS (5 products)
            // ==========================================
            [
                'name' => 'Krishna Shyama Tulsi',
                'slug' => 'krishna-tulsi-plant',
                'sku' => 'PLN-HRB-01',
                'category_slug' => 'herbal-medicinal-plants',
                'brand_key' => 'bioflora-organics',
                'price' => 79.00,
                'compare_at_price' => 99.00,
                'cost_price' => 30.00,
                'stock' => 45,
                'is_featured' => true,
                'short_description' => 'Sacred Indian Holy Basil with purplish-green leaves, revered for spiritual and Ayurvedic wellness.',
                'full_description' => 'Ocimum sanctum (Krishna Tulsi) is steeped in traditional Ayurvedic medicine for immunity, respiratory wellness, and stress relief. Emits a warm peppery clove-like aroma.',
                'custom_attributes' => [
                    'botanical_name' => 'Ocimum sanctum',
                    'light_requirement' => 'Direct Morning Sunlight (4-5 hours)',
                    'watering' => 'Daily light watering, avoid waterlogging',
                    'suitable_location' => 'Courtyard / Balcony Shrine',
                    'plant_height' => '10 - 14 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Aloe Vera Barbadensis',
                'slug' => 'aloe-vera-medicinal',
                'sku' => 'PLN-HRB-02',
                'category_slug' => 'herbal-medicinal-plants',
                'brand_key' => 'bioflora-organics',
                'price' => 129.00,
                'compare_at_price' => 169.00,
                'cost_price' => 50.00,
                'stock' => 40,
                'is_featured' => false,
                'short_description' => 'Thick succulent spears packed with soothing, nutrient-rich botanical gel for skin and wellness.',
                'full_description' => 'Aloe barbadensis miller is a powerhouse medicinal succulent. Its soothing inner gel calms skin burns, hydrates, and supports digestion. Thrives on minimal water.',
                'custom_attributes' => [
                    'botanical_name' => 'Aloe barbadensis miller',
                    'light_requirement' => 'Bright Sunlight',
                    'watering' => 'Once every 8-10 days',
                    'suitable_location' => 'Sunny Windowsills / Balcony',
                    'plant_height' => '10 - 15 inches',
                    'maintenance_level' => 'Very Easy',
                ],
            ],
            [
                'name' => 'Desi Pudina Mint',
                'slug' => 'desi-pudina-mint',
                'sku' => 'PLN-HRB-03',
                'category_slug' => 'herbal-medicinal-plants',
                'brand_key' => 'bioflora-organics',
                'price' => 69.00,
                'compare_at_price' => 89.00,
                'cost_price' => 25.00,
                'stock' => 35,
                'is_featured' => false,
                'short_description' => 'Rapidly spreading culinary herb with refreshing aromatic leaves, essential for Indian chutneys.',
                'full_description' => 'Mentha spicata (Pudina) provides endless fresh aromatic mint leaves for summer beverages, raitas, and chutneys. Prefers moist soil and partial afternoon shade.',
                'custom_attributes' => [
                    'botanical_name' => 'Mentha spicata',
                    'light_requirement' => 'Morning Sun / Partial Afternoon Shade',
                    'watering' => 'Keep soil consistently damp',
                    'suitable_location' => 'Kitchen Garden / Hanging Pots',
                    'plant_height' => '6 - 10 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Citronella Lemongrass',
                'slug' => 'citronella-lemongrass',
                'sku' => 'PLN-HRB-04',
                'category_slug' => 'herbal-medicinal-plants',
                'brand_key' => 'bioflora-organics',
                'price' => 99.00,
                'compare_at_price' => 129.00,
                'cost_price' => 40.00,
                'stock' => 30,
                'is_featured' => false,
                'short_description' => 'Fragrant clumping herb with citrusy stalks, perfect for herbal tea and repelling mosquitoes.',
                'full_description' => 'Cymbopogon citratus (Lemongrass) produces fresh lemon-scented tall blades. Cherished in chai and culinary broths, while naturally discouraging mosquitoes.',
                'custom_attributes' => [
                    'botanical_name' => 'Cymbopogon citratus',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Regular moderate watering',
                    'suitable_location' => 'Balconies / Garden Perimeter',
                    'plant_height' => '16 - 22 inches',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Meetha Neem Curry Leaf',
                'slug' => 'curry-leaf-meetha-neem',
                'sku' => 'PLN-HRB-05',
                'category_slug' => 'herbal-medicinal-plants',
                'brand_key' => 'bioflora-organics',
                'price' => 119.00,
                'compare_at_price' => 149.00,
                'cost_price' => 45.00,
                'stock' => 35,
                'is_featured' => true,
                'short_description' => 'Indispensable aromatic cooking herb yielding richly scented leaves for Indian tempering.',
                'full_description' => 'Murraya koenigii (Kadi Patta / Meetha Neem) is the quintessential Indian kitchen garden plant. Supplies fresh fragrant leaves for tadkas, curries, and natural hair tonics.',
                'custom_attributes' => [
                    'botanical_name' => 'Murraya koenigii',
                    'light_requirement' => 'Warm Direct Sunlight',
                    'watering' => 'Alternate days, well-draining soil',
                    'suitable_location' => 'Kitchen Terrace / Sunny Garden',
                    'plant_height' => '14 - 18 inches',
                    'maintenance_level' => 'Moderate',
                ],
            ],

            // ==========================================
            // 7. FLOWERING SAPLINGS (5 products)
            // ==========================================
            [
                'name' => 'Hybrid Tea Rose Sapling',
                'slug' => 'hybrid-rose-sapling',
                'sku' => 'PLN-SPL-01',
                'category_slug' => 'flowering-saplings',
                'brand_key' => 'verdant-living',
                'price' => 99.00,
                'compare_at_price' => 139.00,
                'cost_price' => 40.00,
                'stock' => 40,
                'is_featured' => false,
                'short_description' => 'Vigorous young rooted rose sapling in nursery polybag ready for container repotting.',
                'full_description' => 'Nursery-propagated rooted rose sapling with well-established root system and healthy bud eye. Ready to be transplanted into an 8-10 inch planter or garden bed.',
                'custom_attributes' => [
                    'botanical_name' => 'Rosa x hybrida',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Daily in polybag',
                    'suitable_location' => 'Outdoor Beds / Pots',
                    'plant_height' => '6 - 10 inches (starter sapling)',
                    'maintenance_level' => 'Moderate',
                ],
            ],
            [
                'name' => 'Dwarf Hibiscus Sapling Starter',
                'slug' => 'dwarf-hibiscus-sapling',
                'sku' => 'PLN-SPL-02',
                'category_slug' => 'flowering-saplings',
                'brand_key' => 'verdant-living',
                'price' => 79.00,
                'compare_at_price' => 109.00,
                'cost_price' => 30.00,
                'stock' => 35,
                'is_featured' => false,
                'short_description' => 'Healthy rooted young hibiscus sapling, ready for quick establishment and early flower buds.',
                'full_description' => 'Compact-growing hibiscus sapling propagated from disease-free parent stock. Rapidly establishes strong roots and begins blooming within 6-8 weeks of repotting.',
                'custom_attributes' => [
                    'botanical_name' => 'Hibiscus rosa-sinensis nana',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Regular',
                    'suitable_location' => 'Balcony Planter Beds',
                    'plant_height' => '6 - 9 inches (starter sapling)',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Raat Ki Rani Night Jasmine Sapling',
                'slug' => 'night-jasmine-sapling',
                'sku' => 'PLN-SPL-03',
                'category_slug' => 'flowering-saplings',
                'brand_key' => 'verdant-living',
                'price' => 89.00,
                'compare_at_price' => 119.00,
                'cost_price' => 35.00,
                'stock' => 30,
                'is_featured' => false,
                'short_description' => 'Young starter sapling of legendary night-scented jasmine, celebrated for nocturnal fragrance.',
                'full_description' => 'Cestrum nocturnum (Raat Ki Rani) produces clusters of small greenish-white flowers that release an intoxicating sweet scent after dusk. Vigorous grower once rooted.',
                'custom_attributes' => [
                    'botanical_name' => 'Cestrum nocturnum',
                    'light_requirement' => 'Full Sun to Partial Shade',
                    'watering' => 'Moderate',
                    'suitable_location' => 'Courtyard / Bedroom Window Border',
                    'plant_height' => '8 - 12 inches (starter sapling)',
                    'maintenance_level' => 'Easy',
                ],
            ],
            [
                'name' => 'Paper Flower Bougainvillea Sapling',
                'slug' => 'bougainvillea-sapling',
                'sku' => 'PLN-SPL-04',
                'category_slug' => 'flowering-saplings',
                'brand_key' => 'verdant-living',
                'price' => 89.00,
                'compare_at_price' => 119.00,
                'cost_price' => 35.00,
                'stock' => 35,
                'is_featured' => false,
                'short_description' => 'Rooted nursery sapling ready to train along boundary grills, pillars, or balcony trellises.',
                'full_description' => 'Hardy young bougainvillea rooted cutting in nursery polybag. Grows vigorously into a drought-tolerant climbing spectacle under intense summer sun.',
                'custom_attributes' => [
                    'botanical_name' => 'Bougainvillea spectabilis',
                    'light_requirement' => 'Full Sunlight',
                    'watering' => 'Low once established',
                    'suitable_location' => 'Outdoor Walls / Pergolas',
                    'plant_height' => '8 - 12 inches (starter sapling)',
                    'maintenance_level' => 'Very Low Maintenance',
                ],
            ],
            [
                'name' => 'French Marigold Nursery Saplings 4-Pack',
                'slug' => 'marigold-saplings-pack',
                'sku' => 'PLN-SPL-05',
                'category_slug' => 'flowering-saplings',
                'brand_key' => 'verdant-living',
                'price' => 119.00,
                'compare_at_price' => 149.00,
                'cost_price' => 45.00,
                'stock' => 30,
                'is_featured' => false,
                'short_description' => 'Convenient 4-pack of healthy seasonal marigold saplings ready for flower border beds.',
                'full_description' => 'Set of 4 well-rooted nursery marigold starters. Ideal for instant floral border planting, vegetable companion gardening, and festive autumn-winter blooms.',
                'custom_attributes' => [
                    'botanical_name' => 'Tagetes patula',
                    'light_requirement' => 'Full Sun',
                    'watering' => 'Moderate',
                    'suitable_location' => 'Garden Borders / Window Boxes',
                    'plant_height' => '4 - 6 inches (4-pack)',
                    'maintenance_level' => 'Easy',
                ],
            ],

            // ==========================================
            // 8. TERRACOTTA POTS (5 products)
            // ==========================================
            [
                'name' => '6-Inch Classic Terracotta Pot',
                'slug' => 'classic-terracotta-pot-6in',
                'sku' => 'POT-TER-01',
                'category_slug' => 'terracotta-pots',
                'brand_key' => 'leafcraft-pottery',
                'price' => 119.00,
                'compare_at_price' => 149.00,
                'cost_price' => 45.00,
                'stock' => 40,
                'is_featured' => true,
                'short_description' => 'Traditional breathable red clay pot with bottom drainage hole, perfect for herbs and small plants.',
                'full_description' => 'Hand-thrown by Indian potters using natural clay. Highly porous terracotta walls allow air and moisture to circulate freely through root zones, preventing root rot.',
                'custom_attributes' => [
                    'material' => '100% Natural Red River Clay',
                    'diameter' => '6 Inches',
                    'height' => '5.5 Inches',
                    'drainage' => 'Center Drainage Hole Included',
                    'finish' => 'Raw Natural Matte Terracotta',
                    'usage' => 'Indoor & Outdoor',
                ],
            ],
            [
                'name' => '8-Inch Ribbed Terracotta Planter',
                'slug' => 'ribbed-terracotta-planter-8in',
                'sku' => 'POT-TER-02',
                'category_slug' => 'terracotta-pots',
                'brand_key' => 'leafcraft-pottery',
                'price' => 199.00,
                'compare_at_price' => 249.00,
                'cost_price' => 80.00,
                'stock' => 35,
                'is_featured' => false,
                'short_description' => 'Mid-sized decorative earthenware planter with horizontal ribbed texture and drainage hole.',
                'full_description' => 'Sturdy 8-inch terracotta planter offering optimal soil volume for flowering shrubs, indoor palms, and table plants. Porous kiln-fired clay ensures healthy root aeration.',
                'custom_attributes' => [
                    'material' => 'High-Fired Terracotta Clay',
                    'diameter' => '8 Inches',
                    'height' => '7.5 Inches',
                    'drainage' => 'Pre-Drilled Drainage Hole',
                    'finish' => 'Rustic Ribbed Texture',
                    'usage' => 'Balconies, Patios, Living Rooms',
                ],
            ],
            [
                'name' => '10-Inch Heavy Rim Terracotta Pot',
                'slug' => 'heavy-rim-terracotta-pot-10in',
                'sku' => 'POT-TER-03',
                'category_slug' => 'terracotta-pots',
                'brand_key' => 'leafcraft-pottery',
                'price' => 299.00,
                'compare_at_price' => 379.00,
                'cost_price' => 120.00,
                'stock' => 25,
                'is_featured' => false,
                'short_description' => 'Substantial heavy-rimmed outdoor clay pot built to support mature fruit and ornamental shrubs.',
                'full_description' => 'Heavy-duty 10-inch red clay container with reinforced top rim for stability against outdoor winds. Perfect for fruiting lemons, ficus, and outdoor flowering bushes.',
                'custom_attributes' => [
                    'material' => 'Natural Earthen Clay',
                    'diameter' => '10 Inches',
                    'height' => '9.5 Inches',
                    'drainage' => 'Wide Center Drainage Hole',
                    'finish' => 'Smooth Earthen Matte',
                    'usage' => 'Terrace & Garden Shrub Planter',
                ],
            ],
            [
                'name' => 'Hand-Carved Floral Terracotta Pot',
                'slug' => 'decorative-floral-terracotta-pot',
                'sku' => 'POT-TER-04',
                'category_slug' => 'terracotta-pots',
                'brand_key' => 'leafcraft-pottery',
                'price' => 349.00,
                'compare_at_price' => 429.00,
                'cost_price' => 150.00,
                'stock' => 20,
                'is_featured' => true,
                'short_description' => 'Artisanal hand-embossed terracotta planter showcasing traditional Indian floral motifs.',
                'full_description' => 'Individually handcrafted by skilled rural artisans. Features intricate floral relief carvings around the clay body, blending rustic Indian craft with functional plant health.',
                'custom_attributes' => [
                    'material' => 'Artisanal Natural Clay',
                    'diameter' => '8.5 Inches',
                    'height' => '8 Inches',
                    'drainage' => 'Drainage Hole Included',
                    'finish' => 'Hand-Embossed Floral Relief',
                    'usage' => 'Decorative Interior & Patio Accent',
                ],
            ],
            [
                'name' => 'Shallow Terracotta Bonsai & Succulent Bowl',
                'slug' => 'shallow-terracotta-bowl',
                'sku' => 'POT-TER-05',
                'category_slug' => 'terracotta-pots',
                'brand_key' => 'leafcraft-pottery',
                'price' => 249.00,
                'compare_at_price' => 299.00,
                'cost_price' => 100.00,
                'stock' => 25,
                'is_featured' => false,
                'short_description' => 'Wide, shallow round earthenware bowl ideal for miniature dish gardens, bonsai, and succulents.',
                'full_description' => 'Wide-mouth shallow clay bowl providing excellent surface area for grouping succulents, cacti, or training miniature bonsai specimens. Superior drainage prevents moisture buildup.',
                'custom_attributes' => [
                    'material' => 'Natural Porous Clay',
                    'diameter' => '10 Inches',
                    'depth' => '4 Inches',
                    'drainage' => 'Dual Drainage Holes',
                    'finish' => 'Raw Rustic Earthen',
                    'usage' => 'Bonsai, Succulent Arrangements, Water Droplet Dish',
                ],
            ],

            // ==========================================
            // 9. PLANT CARE / POTTING MIX (5 products)
            // ==========================================
            [
                'name' => 'Premium Organic Potting Soil Mix 5kg',
                'slug' => 'premium-potting-mix-5kg',
                'sku' => 'SOIL-ORG-01',
                'category_slug' => 'plant-care',
                'brand_key' => 'bioflora-organics',
                'price' => 199.00,
                'compare_at_price' => 249.00,
                'cost_price' => 80.00,
                'stock' => 50,
                'is_featured' => true,
                'short_description' => 'Ready-to-use enriched potting blend of cocopeat, vermicompost, perlite, and organic bio-fertilizer.',
                'full_description' => 'Expertly balanced soilless potting substrate crafted for container gardening. Retains optimal moisture while providing loose aeration for rapid root development.',
                'custom_attributes' => [
                    'composition' => 'Cocopeat, Vermicompost, Perlite, Neem Cake',
                    'weight' => '5 Kilograms',
                    'ph_range' => '6.2 - 6.8 (Neutral Balanced)',
                    'usage' => 'All Indoor & Outdoor Potted Plants',
                    'certification' => '100% Chemical-Free',
                ],
            ],
            [
                'name' => '100% Pure Organic Vermicompost 5kg',
                'slug' => 'vermicompost-organic-5kg',
                'sku' => 'SOIL-VER-02',
                'category_slug' => 'plant-care',
                'brand_key' => 'bioflora-organics',
                'price' => 149.00,
                'compare_at_price' => 189.00,
                'cost_price' => 60.00,
                'stock' => 45,
                'is_featured' => false,
                'short_description' => 'Nutrient-dense earthworm castings packed with micro-organisms to supercharge soil fertility.',
                'full_description' => 'Premium aged vermicompost created by earthworm bio-decomposition. Enriches garden soil with natural nitrogen, phosphorus, potassium, and beneficial mycorrhizal bacteria.',
                'custom_attributes' => [
                    'composition' => '100% Pure Earthworm Castings',
                    'weight' => '5 Kilograms',
                    'nutrient_profile' => 'Rich in NPK and Trace Minerals',
                    'odor' => 'Completely Odorless',
                    'application' => 'Mix 20-30% into topsoil monthly',
                ],
            ],
            [
                'name' => 'Washed Low-EC Cocopeat Brick 5kg',
                'slug' => 'cocopeat-brick-5kg',
                'sku' => 'SOIL-COC-03',
                'category_slug' => 'plant-care',
                'brand_key' => 'bioflora-organics',
                'price' => 169.00,
                'compare_at_price' => 219.00,
                'cost_price' => 70.00,
                'stock' => 40,
                'is_featured' => false,
                'short_description' => 'Triple-washed, low electrical conductivity compressed coconut coir expanding to 75 liters.',
                'full_description' => 'De-salted and washed coconut coir pith compressed into a clean 5kg block. Expands dramatically with water into light, fluffy, moisture-holding substrate for seed starting and potting.',
                'custom_attributes' => [
                    'raw_material' => 'Natural Coconut Husk Fiber',
                    'weight' => '5 Kilograms (Block)',
                    'expanded_volume' => 'Approx 70 - 75 Liters',
                    'ec_level' => 'Low EC (< 0.5 mS/cm)',
                    'usage' => 'Seed germination & soil lightening',
                ],
            ],
            [
                'name' => 'Cold-Pressed Pure Neem Cake Powder 1kg',
                'slug' => 'pure-neem-cake-1kg',
                'sku' => 'SOIL-NEM-04',
                'category_slug' => 'plant-care',
                'brand_key' => 'bioflora-organics',
                'price' => 129.00,
                'compare_at_price' => 159.00,
                'cost_price' => 50.00,
                'stock' => 40,
                'is_featured' => false,
                'short_description' => 'Organic neem seed fertilizer and natural soil amendment protecting roots against nematodes.',
                'full_description' => 'Residue of organic cold-pressed neem seeds. Acts as a slow-release nitrogen bio-fertilizer while naturally suppressing soil-borne pests, termites, nematodes, and root grubs.',
                'custom_attributes' => [
                    'raw_material' => 'Organic Neem Seed Cake',
                    'weight' => '1 Kilogram',
                    'key_benefit' => 'Natural Pest Repellent & N-Source',
                    'application' => '1-2 Tablespoons per pot monthly',
                    'purity' => '100% Unadulterated',
                ],
            ],
            [
                'name' => 'Steamed Organic Bone Meal Fertilizer 1kg',
                'slug' => 'bone-meal-organic-1kg',
                'sku' => 'SOIL-BON-05',
                'category_slug' => 'plant-care',
                'brand_key' => 'bioflora-organics',
                'price' => 139.00,
                'compare_at_price' => 179.00,
                'cost_price' => 55.00,
                'stock' => 35,
                'is_featured' => false,
                'short_description' => 'Slow-release phosphorus and calcium fertilizer for prolific flowering and strong root systems.',
                'full_description' => 'Finely ground steamed bone meal delivering vital natural phosphorus and calcium. Promotes heavy bud formation in flowering plants and strengthens root infrastructure.',
                'custom_attributes' => [
                    'composition' => 'Steamed Natural Bone Meal',
                    'weight' => '1 Kilogram',
                    'primary_nutrients' => 'High Phosphorus & Organic Calcium',
                    'best_for' => 'Roses, Hibiscus, Fruit Trees & Bulbs',
                    'release_rate' => 'Slow-Release Over 3-4 Months',
                ],
            ],
        ];
    }
}
