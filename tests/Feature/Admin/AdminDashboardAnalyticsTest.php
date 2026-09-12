<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Enums\TenderPricingMode;
use App\Enums\TenderStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tender;
use App\Models\Warehouse;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected Warehouse $warehouse;

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
    }

    public function test_unauthorized_guests_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_dashboard_loads_and_displays_all_analytics_sections(): void
    {
        $this->actingAs($this->admin, 'admin');
        session(['admin_auth_token_version' => $this->admin->auth_token_version]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        // Key section titles
        $response->assertSee('Operations Dashboard');
        $response->assertSee('Collected Paid Revenue');
        $response->assertSee('Avg Paid Order Value');
        $response->assertSee('Products by Category');
        $response->assertSee('Inventory & Warehouses', false);
        $response->assertSee('Orders Overview');
        $response->assertSee('Payment Status');
        $response->assertSee('Tender Status');
        $response->assertSee('Shipping & Logistics', false);
        $response->assertSee('Returns & Refunds', false);
    }

    public function test_dashboard_renders_real_product_and_category_analytics(): void
    {
        $category = Category::factory()->create(['name' => 'Exotic Bonsai', 'slug' => 'exotic-bonsai', 'is_active' => true]);

        $product = Product::factory()->create([
            'name' => 'Ficus Microcarpa Bonsai',
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 12,
            'reserved_quantity' => 0,
            'safety_stock' => 3,
        ]);

        $this->actingAs($this->admin, 'admin');
        session(['admin_auth_token_version' => $this->admin->auth_token_version]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertSee('Exotic Bonsai');
        $response->assertSee('exotic-bonsai');
    }

    public function test_dashboard_renders_real_order_and_payment_analytics(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'shipping_status' => ShippingStatus::FULFILLED,
            'grand_total' => 1450.00,
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'transaction_number' => 'TXN-TEST-123',
            'gateway' => 'razorpay',
            'amount' => 1450.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin');
        session(['admin_auth_token_version' => $this->admin->auth_token_version]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertSee('1,450.00');
        $response->assertSee('Delivered');
    }

    public function test_dashboard_renders_real_tender_analytics(): void
    {
        Tender::factory()->create([
            'name' => 'Delhi Municipal Green Belt Development 2026',
            'status' => TenderStatus::ACTIVE,
            'pricing_mode' => TenderPricingMode::ITEM_WISE,
        ]);

        $this->actingAs($this->admin, 'admin');
        session(['admin_auth_token_version' => $this->admin->auth_token_version]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertSee('Active');
        $response->assertSee('1 Tenders');
    }

    public function test_dashboard_renders_graceful_empty_states_when_no_data(): void
    {
        $this->actingAs($this->admin, 'admin');
        session(['admin_auth_token_version' => $this->admin->auth_token_version]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        // Graceful empty states
        $response->assertSee('No customer orders placed yet');
        $response->assertSee('No payment transactions recorded yet');
        $response->assertSee('No tender records created yet');
    }
}
