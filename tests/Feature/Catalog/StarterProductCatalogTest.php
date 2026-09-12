<?php

namespace Tests\Feature\Catalog;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Order\OrderCalculationService;
use Database\Seeders\AdminRbacSeeder;
use Database\Seeders\DevelopmentAdminSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\StarterProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarterProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            AdminRbacSeeder::class,
            DevelopmentAdminSeeder::class,
            ProductCategorySeeder::class,
            StarterProductCatalogSeeder::class,
        ]);
    }

    public function test_starter_product_catalog_populates_45_unique_products(): void
    {
        $this->assertEquals(45, Product::count());
        $this->assertEquals(45, Product::where('is_active', true)->count());
    }

    public function test_all_nine_approved_categories_have_between_four_and_ten_active_products(): void
    {
        $approvedCategorySlugs = [
            'indoor-plants',
            'flowering-plants',
            'air-purifying',
            'fruit-plants',
            'outdoor-plants',
            'herbal-medicinal-plants',
            'flowering-saplings',
            'terracotta-pots',
            'plant-care',
        ];

        foreach ($approvedCategorySlugs as $slug) {
            $category = Category::where('slug', $slug)->first();
            $this->assertNotNull($category, "Category '{$slug}' must exist in database.");

            $activeProductCount = $category->products()
                ->where('products.is_active', true)
                ->count();

            $this->assertGreaterThanOrEqual(
                4,
                $activeProductCount,
                "Category '{$slug}' must have at least 4 active products (found {$activeProductCount})."
            );
            $this->assertLessThanOrEqual(
                10,
                $activeProductCount,
                "Category '{$slug}' must have no more than 10 active products (found {$activeProductCount})."
            );
        }
    }

    public function test_all_product_slugs_and_skus_are_strictly_unique(): void
    {
        $totalProducts = Product::count();
        $uniqueSlugs = Product::distinct()->count('slug');
        $uniqueSkus = Product::distinct()->count('base_sku');

        $this->assertEquals($totalProducts, $uniqueSlugs, 'Every product must have a unique slug.');
        $this->assertEquals($totalProducts, $uniqueSkus, 'Every product must have a unique base SKU.');

        $totalVariants = ProductVariant::count();
        $uniqueVariantSkus = ProductVariant::distinct()->count('sku');
        $this->assertEquals($totalVariants, $uniqueVariantSkus, 'Every variant must have a unique SKU.');
    }

    public function test_every_product_has_an_active_default_variant_with_valid_price(): void
    {
        $products = Product::with('defaultVariant')->get();

        foreach ($products as $product) {
            $variant = $product->defaultVariant;

            $this->assertNotNull($variant, "Product '{$product->name}' must have a default variant.");
            $this->assertTrue($variant->is_active, "Default variant for '{$product->name}' must be active.");
            $this->assertGreaterThan(0, (float) $variant->price, "Product '{$product->name}' must have a positive selling price.");

            if ($variant->compare_at_price !== null) {
                $this->assertGreaterThanOrEqual(
                    (float) $variant->price,
                    (float) $variant->compare_at_price,
                    "Compare-at price for '{$product->name}' must be greater than or equal to selling price."
                );
            }
        }
    }

    public function test_every_variant_has_positive_available_stock_in_main_warehouse(): void
    {
        $products = Product::with('defaultVariant.inventories')->get();

        foreach ($products as $product) {
            $variant = $product->defaultVariant;
            $this->assertGreaterThan(
                0,
                $variant->available_stock,
                "Product '{$product->name}' variant must have positive available stock."
            );
            $this->assertTrue(
                $product->has_stock,
                "Product '{$product->name}' has_stock attribute must be true."
            );
        }
    }

    public function test_landscaping_is_not_a_product_category_and_not_assigned_to_any_product(): void
    {
        $this->assertNull(
            Category::where('slug', 'landscaping')->orWhere('slug', 'landscaping-services')->first(),
            'Landscaping must not exist in categories table.'
        );

        $this->assertNull(
            Product::where('slug', 'landscaping')->first(),
            'Landscaping must not exist in products table.'
        );
    }

    public function test_products_with_local_images_have_valid_storage_asset_url(): void
    {
        $productsWithImages = Product::whereHas('images')->with('primaryImage')->get();

        $this->assertNotEmpty($productsWithImages, 'At least some starter products must have local images.');

        foreach ($productsWithImages as $product) {
            $url = $product->primaryImage->url;
            $this->assertStringContainsString('storage/products/catalog/', $url);
            $this->assertNotEmpty($product->primaryImage->alt_text);
        }
    }

    public function test_pdp_displays_botanical_specifications_and_delivery_terms(): void
    {
        $product = Product::where('slug', 'money-plant-golden-pothos')->firstOrFail();

        $response = $this->get(route('products.show', $product->slug));
        $response->assertOk();
        $response->assertSee('Money Plant Golden Pothos');
        $response->assertSee('Epipremnum aureum');
        $response->assertSee('Botanical Specifications');
        $response->assertSee('Direct Nursery Delivery');
        $response->assertSee('ONLY within Delhi NCR');
        $response->assertSee('delivered within 3 days');
        $response->assertSee('ABOVE ₹1,000');
        $response->assertDontSee('₹150');
        $response->assertDontSee('₹99');
    }

    public function test_shop_and_category_pages_display_starter_products(): void
    {
        // Shop page (page 1 lists featured starter products)
        $response = $this->get(route('shop.index'));
        $response->assertOk();
        $response->assertSee('Plant Catalog');
        $response->assertSee('45');

        // Shop search query for specific starter product
        $searchResponse = $this->get(route('shop.index', ['q' => 'Money Plant']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Money Plant Golden Pothos');

        // Category pages for each of the 9 categories display their products
        $categories = Category::all();
        foreach ($categories as $cat) {
            $catResponse = $this->get(route('categories.show', $cat->slug));
            $catResponse->assertOk();
            $catResponse->assertSee($cat->name);

            // Each category has 5 products, at least one should be visible on its category page
            $catProduct = $cat->products()->where('products.is_active', true)->first();
            if ($catProduct) {
                $catResponse->assertSee($catProduct->name);
            }
        }
    }

    public function test_starter_products_can_be_added_to_cart_and_delivery_rules_hold(): void
    {
        $product = Product::where('slug', 'money-plant-golden-pothos')->firstOrFail();
        $variant = $product->defaultVariant;

        $response = $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();

        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertOk();
        $cartResponse->assertSee('Money Plant Golden Pothos');

        // Verify delivery rules with OrderCalculationService
        $calculationService = app(OrderCalculationService::class);

        // 1. Strictly > ₹1,000 qualifies for free delivery
        $this->assertEquals('0.00', $calculationService->calculateShipping('1000.01'));
        $this->assertEquals('0.00', $calculationService->calculateShipping('1500.00'));

        // 2. Exactly ₹1,000 does NOT qualify for free delivery; uses pending configured flat rate (default 0.00)
        $configuredRate = config('ecommerce.shipping.flat_rate', '0.00');
        $this->assertEquals($configuredRate, $calculationService->calculateShipping('1000.00'));

        // 3. Below ₹1,000 does NOT qualify for free delivery; uses pending configured flat rate
        $this->assertEquals($configuredRate, $calculationService->calculateShipping('598.00'));

        // 4. Assert no invented commercial fees (₹150 or ₹99) are hardcoded or set
        $this->assertNotEquals('150.00', $configuredRate, 'Delivery charge must NOT be invented as ₹150.');
        $this->assertNotEquals('99.00', $configuredRate, 'Delivery charge must NOT be invented as ₹99.');
    }

    public function test_admin_can_view_and_edit_starter_product(): void
    {
        $admin = Admin::firstOrFail();
        session(['admin_auth_token_version' => $admin->auth_token_version]);
        $product = Product::where('slug', 'money-plant-golden-pothos')->firstOrFail();

        // Admin product listing
        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.index', ['search' => 'Money Plant']));
        $response->assertOk();
        $response->assertSee('Money Plant Golden Pothos');

        // Admin product edit view
        $editResponse = $this->actingAs($admin, 'admin')->get(route('admin.products.edit', $product));
        $editResponse->assertOk();
        $editResponse->assertSee($product->name);

        // Admin update product name & description
        $updateResponse = $this->actingAs($admin, 'admin')->put(route('admin.products.update', $product), [
            'name' => 'Money Plant Golden Pothos (Updated by Admin)',
            'slug' => 'money-plant-golden-pothos',
            'base_sku' => 'PLN-IND-01',
            'short_description' => 'Updated description by admin.',
            'full_description' => 'Full detailed description updated by admin.',
            'is_active' => true,
            'category_ids' => [$product->categories->first()->id],
            'primary_category_id' => $product->categories->first()->id,
        ]);

        $updateResponse->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Money Plant Golden Pothos (Updated by Admin)',
            'short_description' => 'Updated description by admin.',
        ]);
    }

    public function test_seeder_is_idempotent_and_does_not_create_duplicate_products(): void
    {
        $initialProductCount = Product::count();
        $initialVariantCount = ProductVariant::count();

        // Re-run the seeder
        $this->seed(StarterProductCatalogSeeder::class);

        $this->assertEquals($initialProductCount, Product::count(), 'Re-running seeder must not change product count.');
        $this->assertEquals($initialVariantCount, ProductVariant::count(), 'Re-running seeder must not change variant count.');
    }
}
