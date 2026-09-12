<?php

namespace Tests\Feature\Catalog;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Catalog\ProductManagementService;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyPlantStockTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;

    protected Category $category;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminRbacSeeder::class);

        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->warehouse = Warehouse::firstOrCreate(
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

        $this->category = Category::firstOrCreate(
            ['slug' => 'table-top-plants'],
            [
                'name' => 'Table Top Plants',
                'description' => 'Compact desk and table plants.',
                'is_active' => true,
            ]
        );
    }

    public function test_manually_created_product_with_initial_stock_creates_inventory_and_is_in_stock_on_storefront(): void
    {
        $service = app(ProductManagementService::class);

        $product = $service->createProduct([
            'name' => 'Money Plant Tabletop Specimen',
            'slug' => 'money-plant-tabletop-specimen',
            'base_sku' => 'PLN-MNT-TEST-01',
            'short_description' => 'Lush green trailing money plant in compact tabletop nursery arrangement.',
            'category_ids' => [$this->category->id],
            'primary_category_id' => $this->category->id,
            'initial_price' => '249.00',
            'initial_compare_at_price' => '299.00',
            'initial_stock' => 20,
            'safety_stock' => 3,
            'is_active' => true,
        ], $this->admin);

        // 1. Diagnose inventory chain
        $this->assertTrue($product->is_active);
        $this->assertCount(1, $product->variants);

        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);
        $this->assertTrue($variant->is_active);
        $this->assertTrue($variant->is_default);
        $this->assertEquals('PLN-MNT-TEST-01', $variant->sku);
        $this->assertEquals('249.00', (string) $variant->price);

        // Inventory record in MAIN-WH-01
        $inventory = Inventory::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertNotNull($inventory, 'Inventory record must be created in MAIN-WH-01');
        $this->assertEquals(20, $inventory->quantity);
        $this->assertEquals(0, $inventory->reserved_quantity);
        $this->assertEquals(20, $inventory->available_quantity);
        $this->assertEquals(20, $variant->available_stock);
        $this->assertTrue($product->has_stock);

        // 2. Storefront product page shows In Stock
        $response = $this->get(route('products.show', $product->slug));
        $response->assertOk();
        $response->assertSee('In Stock');

        // 3. Can add to cart
        $cartResponse = $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $cartResponse->assertRedirect();
        $cart = Cart::where('session_id', session()->getId())->first();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->items);
        $this->assertEquals(2, $cart->items->first()->quantity);
    }

    public function test_zero_stock_product_shows_out_of_stock_and_cannot_be_added_to_cart(): void
    {
        $service = app(ProductManagementService::class);

        $product = $service->createProduct([
            'name' => 'Zero Stock Test Plant',
            'slug' => 'zero-stock-test-plant',
            'base_sku' => 'PLN-ZERO-TEST',
            'category_ids' => [$this->category->id],
            'primary_category_id' => $this->category->id,
            'initial_price' => '199.00',
            'initial_stock' => 0,
            'is_active' => true,
        ], $this->admin);

        $variant = $product->defaultVariant;
        $this->assertEquals(0, $variant->available_stock);
        $this->assertFalse($product->has_stock);

        // Storefront product page shows Out of Stock
        $response = $this->get(route('products.show', $product->slug));
        $response->assertOk();
        $response->assertSee('Out of Stock');

        // Cart addition is rejected
        $cartResponse = $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cartResponse->assertSessionHasErrors();
    }

    public function test_admin_product_controller_store_accepts_initial_stock(): void
    {
        $this->actingAs($this->admin, 'admin');
        session(['admin_auth_token_version' => $this->admin->auth_token_version]);

        $response = $this->post(route('admin.products.store'), [
            'name' => 'Admin Controller Money Plant',
            'slug' => 'admin-controller-money-plant',
            'base_sku' => 'PLN-MNT-CTRL-01',
            'category_ids' => [$this->category->id],
            'primary_category_id' => $this->category->id,
            'initial_price' => '279.00',
            'initial_stock' => 15,
            'safety_stock' => 3,
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $product = Product::where('slug', 'admin-controller-money-plant')->first();
        $this->assertNotNull($product);
        $variant = $product->defaultVariant;
        $this->assertNotNull($variant);
        $this->assertEquals(15, $variant->available_stock);
        $this->assertTrue($product->has_stock);
    }
}
