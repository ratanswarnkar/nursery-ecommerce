<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnStatus;
use App\Enums\ShippingStatus;
use App\Enums\TenderBillStatus;
use App\Enums\TenderDocumentType;
use App\Enums\TenderPricingMode;
use App\Enums\TenderStatus;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use App\Models\Tender;
use App\Models\TenderBill;
use App\Models\TenderDocument;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase11AdminControlTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $superAdmin;

    protected Admin $restrictedAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminRbacSeeder::class);

        $this->superAdmin = Admin::factory()->create(['is_active' => true]);
        $this->superAdmin->assignRole('Super Admin');

        $this->restrictedAdmin = Admin::factory()->create(['is_active' => true]);
        $this->restrictedAdmin->assignRole('Staff');
    }

    protected function asSuperAdmin(): self
    {
        $this->actingAs($this->superAdmin, 'admin');
        session(['admin_auth_token_version' => $this->superAdmin->auth_token_version]);

        return $this;
    }

    protected function asRestrictedAdmin(): self
    {
        $this->actingAs($this->restrictedAdmin, 'admin');
        session(['admin_auth_token_version' => $this->restrictedAdmin->auth_token_version]);

        return $this;
    }

    /**
     * Test all 20 sidebar routes return HTTP 200 for Super Admin.
     */
    public function test_super_admin_can_access_all_sidebar_modules(): void
    {
        $routes = [
            'admin.dashboard',
            'admin.products.index',
            'admin.categories.index',
            'admin.brands.index',
            'admin.attributes.index',
            'admin.inventory.index',
            'admin.inventory.movements',
            'admin.warehouses.index',
            'admin.orders.index',
            'admin.payments.index',
            'admin.shipments.index',
            'admin.returns.index',
            'admin.invoices.index',
            'admin.customers.index',
            'admin.tenders.index',
            'admin.admins.index',
            'admin.roles.index',
            'admin.audit-logs.index',
            'admin.login-activity.index',
            'admin.settings.index',
        ];

        $this->asSuperAdmin();

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertOk();
        }
    }

    /**
     * Test permission enforcement: unauthorized admin is forbidden (403).
     */
    public function test_restricted_admin_is_forbidden_from_privileged_modules(): void
    {
        $restrictedRoutes = [
            'admin.payments.index',
            'admin.invoices.index',
            'admin.tenders.index',
            'admin.admins.index',
            'admin.roles.index',
            'admin.audit-logs.index',
            'admin.settings.index',
        ];

        $this->asRestrictedAdmin();

        foreach ($restrictedRoutes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertForbidden();
        }
    }

    /**
     * Test full Tender Management workflow.
     */
    public function test_complete_tender_management_workflow(): void
    {
        $this->asSuperAdmin();
        Storage::fake('local');

        // 1. Create Tender
        $createResponse = $this->post(route('admin.tenders.store'), [
            'tender_number' => 'TND-DEL-2026-001',
            'name' => 'Delhi Municipal Park Afforestation',
            'department_name' => 'Department of Forests & Wildlife, GNCTD',
            'project_name' => 'Green Delhi Mission 2026',
            'description' => 'Procurement and planting of 5,000 native avenue trees.',
            'original_soq_value' => '1500000.00',
            'awarded_value' => '1425000.00',
            'below_above_percentage' => '-5.00',
            'pricing_mode' => TenderPricingMode::PERCENTAGE_OVERALL->value,
            'start_date' => '2026-10-01',
            'end_date' => '2027-03-31',
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('tenders', [
            'tender_number' => 'TND-DEL-2026-001',
            'status' => TenderStatus::DRAFT->value,
        ]);

        $tender = Tender::where('tender_number', 'TND-DEL-2026-001')->firstOrFail();

        // 2. Add SOQ Item
        $itemResponse = $this->post(route('admin.tenders.items.store', $tender), [
            'item_code' => 'SOQ-01',
            'description' => 'Neem Saplings 6ft height in 25L root bags',
            'unit' => 'nos',
            'government_quantity' => 1000,
            'government_rate' => '450.00',
            'quoted_rate' => '427.50',
        ]);
        $itemResponse->assertRedirect();
        $this->assertDatabaseHas('tender_items', [
            'tender_id' => $tender->id,
            'item_code' => 'SOQ-01',
        ]);

        // 3. Add Requirement Demand Call
        $reqResponse = $this->post(route('admin.tenders.requirements.store', $tender), [
            'requirement_number' => 'REQ-PHASE-1',
            'requirement_date' => '2026-10-15',
            'notes' => 'Urgent batch for Outer Ring Road sector',
        ]);
        $reqResponse->assertRedirect();
        $this->assertDatabaseHas('tender_requirements', [
            'tender_id' => $tender->id,
            'requirement_number' => 'REQ-PHASE-1',
        ]);

        // 4. Upload Document
        $file = UploadedFile::fake()->create('contract_work_order.pdf', 500, 'application/pdf');
        $docResponse = $this->post(route('admin.tenders.documents.store', $tender), [
            'document_type' => TenderDocumentType::AWARDED_DOCUMENT->value,
            'document' => $file,
        ]);
        $docResponse->assertRedirect();
        $this->assertDatabaseHas('tender_documents', [
            'tender_id' => $tender->id,
            'original_filename' => 'contract_work_order.pdf',
        ]);

        $document = TenderDocument::where('tender_id', $tender->id)->firstOrFail();
        Storage::disk('local')->assertExists($document->stored_filename);

        // 5. Download Document
        $downloadResponse = $this->get(route('admin.tenders.documents.download', [$tender, $document]));
        $downloadResponse->assertOk();

        // 6. Transition Status to ACTIVE
        $statusResponse = $this->post(route('admin.tenders.update-status', $tender), [
            'status' => TenderStatus::ACTIVE->value,
        ]);
        $statusResponse->assertRedirect();
        $this->assertEquals(TenderStatus::ACTIVE, $tender->fresh()->status);

        // 7. Toggle Special Billing
        $toggleResponse = $this->post(route('admin.tenders.toggle-special-billing', $tender));
        $toggleResponse->assertRedirect();
        $this->assertTrue($tender->fresh()->special_billing_enabled);

        // 8. Create Tender Bill (RA Bill)
        $billResponse = $this->post(route('admin.tenders.bills.store', $tender), [
            'bill_number' => 'RA-BILL-01',
            'bill_date' => '2026-11-01',
            'subtotal' => '200000.00',
            'tax_amount' => '36000.00',
            'grand_total' => '236000.00',
            'notes' => 'First RA bill for October progress',
        ]);
        $billResponse->assertRedirect();
        $this->assertDatabaseHas('tender_bills', [
            'tender_id' => $tender->id,
            'bill_number' => 'RA-BILL-01',
        ]);

        $bill = TenderBill::where('tender_id', $tender->id)->firstOrFail();

        // 9. Update Bill Status
        $billStatusResponse = $this->post(route('admin.tenders.bills.update-status', [$tender, $bill]), [
            'status' => TenderBillStatus::APPROVED->value,
        ]);
        $billStatusResponse->assertRedirect();
        $this->assertEquals(TenderBillStatus::APPROVED, $bill->fresh()->status);
    }

    /**
     * Test Customer Management (List, Show, Toggle).
     */
    public function test_customer_management(): void
    {
        $this->asSuperAdmin();

        $customer = Customer::factory()->create([
            'name' => 'Rajesh Sharma',
            'phone' => '+919876543210',
            'email' => 'rajesh@example.com',
            'is_active' => true,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'recipient_name' => 'Rajesh Sharma',
            'phone' => '+919876543210',
        ]);

        // 1. Index
        $response = $this->get(route('admin.customers.index', ['search' => 'Rajesh']));
        $response->assertOk();
        $response->assertSee('Rajesh Sharma');

        // 2. Show
        $showResponse = $this->get(route('admin.customers.show', $customer));
        $showResponse->assertOk();
        $showResponse->assertSee('Rajesh Sharma');
        $showResponse->assertSee('+919876543210');

        // 3. Toggle Status
        $toggleResponse = $this->post(route('admin.customers.toggle-status', $customer));
        $toggleResponse->assertRedirect();
        $this->assertFalse($customer->fresh()->is_active);

        $toggleResponse2 = $this->post(route('admin.customers.toggle-status', $customer));
        $toggleResponse2->assertRedirect();
        $this->assertTrue($customer->fresh()->is_active);
    }

    /**
     * Test Administrator and Role Management.
     */
    public function test_admin_user_and_role_management(): void
    {
        $this->asSuperAdmin();

        // 1. Create Admin
        $createResponse = $this->post(route('admin.admins.store'), [
            'name' => 'Ops Manager Amit',
            'email' => 'amit.ops@nurseryecommerce.local',
            'password' => 'SecurePass#2026',
            'password_confirmation' => 'SecurePass#2026',
            'roles' => ['Admin'],
            'is_active' => 1,
        ]);

        $createResponse->assertRedirect(route('admin.admins.index'));
        $this->assertDatabaseHas('admins', [
            'email' => 'amit.ops@nurseryecommerce.local',
        ]);

        $newAdmin = Admin::where('email', 'amit.ops@nurseryecommerce.local')->firstOrFail();
        $this->assertTrue($newAdmin->hasRole('Admin'));

        // 2. Edit Admin
        $updateResponse = $this->put(route('admin.admins.update', $newAdmin), [
            'name' => 'Senior Ops Manager Amit',
            'email' => 'amit.ops@nurseryecommerce.local',
            'roles' => ['Admin'],
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect(route('admin.admins.index'));
        $this->assertEquals('Senior Ops Manager Amit', $newAdmin->fresh()->name);

        // 3. Self-Deactivation Prevention
        $selfDeactivateResponse = $this->put(route('admin.admins.update', $this->superAdmin), [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'roles' => ['Super Admin'],
            'is_active' => 0,
        ]);
        $selfDeactivateResponse->assertSessionHas('error');
        $this->assertTrue($this->superAdmin->fresh()->is_active);

        // 4. Role Matrix View
        $roleResponse = $this->get(route('admin.roles.index'));
        $roleResponse->assertOk();
        $roleResponse->assertSee('Super Admin');

        $superAdminRole = Role::findByName('Super Admin', 'admin');
        $roleShowResponse = $this->get(route('admin.roles.show', $superAdminRole));
        $roleShowResponse->assertOk();
        $roleShowResponse->assertSee('dashboard.view');
    }

    /**
     * Test Payments, Shipments, Returns, and Invoices.
     */
    public function test_sales_and_fulfillment_listing_modules(): void
    {
        $this->asSuperAdmin();

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-TEST-9999',
            'status' => OrderStatus::PROCESSING,
            'payment_status' => PaymentStatus::PAID,
            'grand_total' => 1250.00,
        ]);

        $payment = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'transaction_number' => 'TXN-TEST-9999',
            'status' => PaymentStatus::PAID,
            'amount' => 1250.00,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'tracking_number' => 'TRACK-9999-DEL',
            'carrier' => 'Delhivery Express',
            'shipping_status' => ShippingStatus::PARTIALLY_FULFILLED,
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => 'Fiddle Leaf Fig',
            'quantity' => 1,
            'price' => 1250.00,
            'subtotal' => 1250.00,
            'total' => 1250.00,
        ]);

        $return = OrderReturn::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'reason' => 'Plant leaves slightly bruised during transport',
            'status' => ReturnStatus::REQUESTED,
            'refund_amount' => 1250.00,
        ]);

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-9999',
            'invoice_date' => now(),
            'currency' => 'INR',
            'subtotal' => 1200.00,
            'tax_amount' => 50.00,
            'shipping_amount' => 0.00,
            'discount_amount' => 0.00,
            'grand_total' => 1250.00,
            'customer_snapshot' => ['name' => $customer->name, 'phone' => $customer->phone],
            'shipping_address_snapshot' => [],
            'billing_address_snapshot' => [],
            'items_snapshot' => [],
            'payment_status_snapshot' => 'paid',
            'order_status_snapshot' => 'processing',
        ]);

        // Payments Index
        $payRes = $this->get(route('admin.payments.index', ['search' => 'TXN-TEST-9999']));
        $payRes->assertOk();
        $payRes->assertSee('TXN-TEST-9999');

        // Shipments Index
        $shipRes = $this->get(route('admin.shipments.index', ['search' => 'TRACK-9999-DEL']));
        $shipRes->assertOk();
        $shipRes->assertSee('TRACK-9999-DEL');

        // Returns Index
        $retRes = $this->get(route('admin.returns.index'));
        $retRes->assertOk();
        $retRes->assertSee('Plant leaves slightly bruised');

        // Invoices Index
        $invRes = $this->get(route('admin.invoices.index', ['search' => 'INV-TEST-9999']));
        $invRes->assertOk();
        $invRes->assertSee('INV-TEST-9999');
    }
}
