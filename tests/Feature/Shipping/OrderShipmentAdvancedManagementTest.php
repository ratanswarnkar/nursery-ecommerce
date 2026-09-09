<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Exceptions\Shipping\IneligibleForShipmentException;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Invoice\InvoiceService;
use App\Services\Shipping\ShipmentService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // 1. Warehouse
    $this->warehouse = Warehouse::create([
        'name' => 'Main Botanical Nursery Hub',
        'code' => 'DEL-HUB-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    // 2. Customer
    $this->customer = Customer::factory()->create([
        'name' => 'Aditi Sharma',
        'phone' => '+919811122233',
        'email' => 'aditi@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    // 3. Customer Address
    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919811122233',
        'address_line_1' => 'Villa 12, Palm Meadows',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
        'is_default' => true,
    ]);

    // 4. Product & Variant
    $this->product = Product::factory()->create([
        'name' => 'Fiddle Leaf Fig',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'FID-FIG-01',
        'price' => 1250.00,
        'is_active' => true,
    ]);

    // 5. Initial Inventory
    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);

    // 6. Eligible Paid & Processing Order
    $this->order = Order::create([
        'order_number' => 'ORD-20260909-SHP01',
        'customer_id' => $this->customer->id,
        'customer_name' => 'Aditi Sharma',
        'customer_phone' => '+919811122233',
        'customer_email' => 'aditi@example.com',
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 2500.00,
        'tax_amount' => 125.00,
        'shipping_amount' => 150.00,
        'discount_amount' => 0.00,
        'grand_total' => 2775.00,
        'shipping_address_json' => [
            'recipient_name' => 'Aditi Sharma',
            'phone' => '+919811122233',
            'address_line_1' => 'Villa 12, Palm Meadows',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
        'billing_address_json' => [
            'recipient_name' => 'Aditi Sharma',
            'phone' => '+919811122233',
            'address_line_1' => 'Villa 12, Palm Meadows',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
    ]);

    $this->orderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => 'Fiddle Leaf Fig',
        'variant_name' => 'Large (12 inch pot)',
        'sku' => 'FID-FIG-01',
        'price' => 1250.00,
        'quantity' => 2,
        'subtotal' => 2500.00,
        'tax_amount' => 125.00,
        'discount_amount' => 0.00,
        'total' => 2625.00,
    ]);

    // 7. Admin with orders.update and orders.view
    Permission::findOrCreate('orders.view', 'admin');
    Permission::findOrCreate('orders.update', 'admin');

    $this->admin = Admin::factory()->create(['auth_token_version' => 1]);
    $this->admin->givePermissionTo(['orders.view', 'orders.update']);

    $this->shipmentService = app(ShipmentService::class);

    // Initial shipment creation
    $this->shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: [
            'carrier' => 'BlueDart Express',
            'tracking_number' => 'BD10000001',
            'tracking_url' => 'https://track.bluedart.com/track?ref=BD10000001',
            'estimated_delivery_at' => '2026-09-15',
            'notes' => 'Original packing notes',
        ],
        admin: $this->admin
    );
});

/*
|--------------------------------------------------------------------------
| Phase 6.5-C: Advanced Shipment Management Tests (1 to 22)
|--------------------------------------------------------------------------
*/

test('1. authorized admin can update shipment tracking information via HTTP endpoint', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'carrier' => 'Delhivery Express',
        'tracking_number' => 'DL99999999',
        'tracking_url' => 'https://delhivery.com/track?awb=DL99999999',
        'estimated_delivery_at' => '2026-09-18',
        'notes' => 'Special live plant cargo handling',
    ]);

    $response->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');

    $freshShipment = $this->shipment->fresh();
    expect($freshShipment->carrier)->toBe('Delhivery Express')
        ->and($freshShipment->tracking_number)->toBe('DL99999999')
        ->and($freshShipment->tracking_url)->toBe('https://delhivery.com/track?awb=DL99999999')
        ->and($freshShipment->estimated_delivery_at->format('Y-m-d'))->toBe('2026-09-18')
        ->and($freshShipment->notes)->toBe('Special live plant cargo handling');
});

test('2. carrier can be updated', function () {
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['carrier' => 'DTDC Express Courier'],
        admin: $this->admin
    );

    expect($this->shipment->fresh()->carrier)->toBe('DTDC Express Courier');
});

test('3. tracking number can be updated', function () {
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['tracking_number' => 'DTDC-NEW-9876'],
        admin: $this->admin
    );

    expect($this->shipment->fresh()->tracking_number)->toBe('DTDC-NEW-9876');
});

test('4. valid HTTP tracking URL is accepted', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'carrier' => 'Local Courier',
        'tracking_url' => 'http://track.localcourier.in/item?id=1234',
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->shipment->fresh()->tracking_url)->toBe('http://track.localcourier.in/item?id=1234');
});

test('5. valid HTTPS tracking URL is accepted', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'carrier' => 'Secure Courier',
        'tracking_url' => 'https://secure.courier.com/track/SEC1234',
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->shipment->fresh()->tracking_url)->toBe('https://secure.courier.com/track/SEC1234');
});

test('6. invalid or non-http/https URL is rejected', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // Test ftp://
    $responseFtp = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'tracking_url' => 'ftp://files.courier.com/track',
    ]);
    $responseFtp->assertSessionHasErrors('tracking_url');

    // Test javascript:
    $responseJs = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'tracking_url' => 'javascript:alert(1)',
    ]);
    $responseJs->assertSessionHasErrors('tracking_url');

    // Test data:
    $responseData = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'tracking_url' => 'data:text/html,<script>alert(1)</script>',
    ]);
    $responseData->assertSessionHasErrors('tracking_url');

    // Test malformed string
    $responseBad = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'tracking_url' => 'not-a-valid-url',
    ]);
    $responseBad->assertSessionHasErrors('tracking_url');
});

test('7. estimated delivery can be updated', function () {
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['estimated_delivery_at' => '2026-09-25 14:30:00'],
        admin: $this->admin
    );

    expect($this->shipment->fresh()->estimated_delivery_at->format('Y-m-d H:i:s'))
        ->toBe('2026-09-25 14:30:00');
});

test('8. notes can be updated', function () {
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['notes' => 'Priority moisture-retaining plant packaging added.'],
        admin: $this->admin
    );

    expect($this->shipment->fresh()->notes)->toBe('Priority moisture-retaining plant packaging added.');
});

test('9. immutable shipment fields cannot be modified through the update request', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $originalOrderId = $this->shipment->order_id;
    $originalSnapshot = $this->shipment->items_snapshot;
    $originalShippedAt = $this->shipment->shipped_at;
    $originalDeliveredAt = $this->shipment->delivered_at;
    $originalStatus = $this->shipment->shipping_status;

    // Malicious payload attempting to rewrite immutable fields
    $response = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'carrier' => 'Updated Carrier',
        'order_id' => 99999,
        'items_snapshot' => [['product_name' => 'Hacked Item', 'quantity' => 100]],
        'shipped_at' => '2020-01-01 00:00:00',
        'delivered_at' => '2020-01-02 00:00:00',
        'shipping_status' => 'returned',
        'payment_status' => 'refunded',
        'grand_total' => '0.00',
    ]);

    $response->assertSessionHasNoErrors();

    $freshShipment = $this->shipment->fresh();
    expect($freshShipment->carrier)->toBe('Updated Carrier')
        ->and($freshShipment->order_id)->toBe($originalOrderId)
        ->and($freshShipment->items_snapshot)->toEqual($originalSnapshot)
        ->and($freshShipment->shipped_at->equalTo($originalShippedAt))->toBeTrue()
        ->and($freshShipment->delivered_at)->toBe($originalDeliveredAt)
        ->and($freshShipment->shipping_status)->toBe($originalStatus);

    $freshOrder = $this->order->fresh();
    expect($freshOrder->payment_status)->toBe(PaymentStatus::PAID)
        ->and((string) $freshOrder->grand_total)->toBe('2775.00');
});

test('10. unauthorized admin cannot update shipment', function () {
    $readOnlyAdmin = Admin::factory()->create(['auth_token_version' => 1]);
    $readOnlyAdmin->givePermissionTo('orders.view'); // missing orders.update

    $this->actingAs($readOnlyAdmin, 'admin');
    session(['admin_auth_token_version' => $readOnlyAdmin->auth_token_version]);

    $response = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'carrier' => 'Unauthorized Attempt',
    ]);

    $response->assertStatus(403);
    expect($this->shipment->fresh()->carrier)->not->toBe('Unauthorized Attempt');
});

test('11. customer cannot update shipment', function () {
    $this->actingAs($this->customer, 'customer');

    // Customer attempt against admin update route
    $response = $this->put(route('admin.orders.shipments.update', [$this->order, $this->shipment]), [
        'carrier' => 'Customer Tampering',
    ]);

    // Admin middleware or permission fails
    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
    expect($this->shipment->fresh()->carrier)->not->toBe('Customer Tampering');
});

test('12. customer can see shipment details for own order', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.orders.show', $this->order->order_number));

    $response->assertOk()
        ->assertSee('Shipment & Delivery Details', false)
        ->assertSee('BlueDart Express')
        ->assertSee('BD10000001')
        ->assertSee('Items Included in Shipment')
        ->assertSee('Fiddle Leaf Fig')
        ->assertSee('Track Package');
});

test('13. customer cannot access another customer shipment', function () {
    $otherCustomer = Customer::factory()->create(['auth_token_version' => 1, 'is_active' => true]);
    $this->actingAs($otherCustomer, 'customer');
    session(['customer_auth_token_version' => $otherCustomer->auth_token_version]);

    // Trying to view the first customer's order and shipment
    $response = $this->get(route('account.orders.show', $this->order->order_number));

    // Must return 404 per established IDOR policy
    $response->assertStatus(404);
});

test('14. shipment update does not change inventory', function () {
    $initialStock = $this->inventory->fresh()->quantity;

    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['carrier' => 'Inventory Safety Test Carrier', 'tracking_number' => 'INV12345'],
        admin: $this->admin
    );

    expect($this->inventory->fresh()->quantity)->toBe($initialStock);
});

test('15. shipment update does not create inventory movements', function () {
    $initialMovements = StockMovement::count();

    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['carrier' => 'Movement Safety Test Carrier'],
        admin: $this->admin
    );

    expect(StockMovement::count())->toBe($initialMovements);
});

test('16. shipment update does not change payment status', function () {
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['carrier' => 'Payment Safety Test'],
        admin: $this->admin
    );

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

test('17. shipment update does not alter invoice data', function () {
    $invoiceService = app(InvoiceService::class);
    $invoice = $invoiceService->getOrCreateInvoiceForOrder($this->order);
    $originalNumber = $invoice->invoice_number;
    $originalTotal = $invoice->grand_total;
    $originalSnapshot = $invoice->items_snapshot;

    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['carrier' => 'Invoice Safety Test', 'tracking_number' => 'INV-TRACK-01'],
        admin: $this->admin
    );

    $freshInvoice = $invoice->fresh();
    expect($freshInvoice->invoice_number)->toBe($originalNumber)
        ->and($freshInvoice->grand_total)->toBe($originalTotal)
        ->and($freshInvoice->items_snapshot)->toEqual($originalSnapshot);
});

test('18. audit event is created for tracking update', function () {
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: [
            'carrier' => 'Audit Test Carrier',
            'tracking_number' => 'AUDIT-TRK-77',
            'tracking_url' => 'https://audit-carrier.com/track',
        ],
        admin: $this->admin
    );

    $audit = AuditLog::where('action', 'order.shipment_tracking_updated')
        ->where('auditable_id', $this->shipment->id)
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->admin_id)->toBe($this->admin->id)
        ->and($audit->new_values['new']['carrier'])->toBe('Audit Test Carrier')
        ->and($audit->new_values['new']['tracking_number'])->toBe('AUDIT-TRK-77')
        ->and($audit->new_values['new']['tracking_url'])->toBe('https://audit-carrier.com/track')
        ->and($audit->new_values['old']['carrier'])->toBe('BlueDart Express');
});

test('19. Track Package link is shown only when tracking URL exists', function () {
    // When tracking_url is present
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $viewWithUrl = $this->get(route('admin.orders.show', $this->order));
    $viewWithUrl->assertOk()
        ->assertSee('Track Package')
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false);

    // Remove tracking_url
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['tracking_url' => null],
        admin: $this->admin
    );

    $viewWithoutUrl = $this->get(route('admin.orders.show', $this->order));
    $viewWithoutUrl->assertOk()
        ->assertDontSee('Track Package');
});

test('20. existing shipment lifecycle tests continue passing', function () {
    // Progress through OUT_FOR_DELIVERY then DELIVERED
    $this->shipmentService->markOutForDelivery($this->shipment, admin: $this->admin);
    expect($this->order->fresh()->status)->toBe(OrderStatus::OUT_FOR_DELIVERY);

    $this->shipmentService->markDelivered($this->shipment, admin: $this->admin);
    expect($this->order->fresh()->status)->toBe(OrderStatus::DELIVERED)
        ->and($this->shipment->fresh()->delivered_at)->not->toBeNull();
});

test('21. duplicate active shipment protection continues working', function () {
    // Order already has active shipment
    expect(fn () => $this->shipmentService->createShipment(
        order: $this->order->fresh(),
        data: ['carrier' => 'Duplicate Attempt'],
        admin: $this->admin
    ))->toThrow(IneligibleForShipmentException::class);
});

test('22. historical item snapshot remains independent from live Product changes', function () {
    // Mutate live product and variant
    $this->product->update(['name' => 'Renamed Monster Monstera']);
    $this->variant->update(['sku' => 'CHANGED-SKU-999', 'price' => 7777.00]);

    // Update tracking info on shipment
    $this->shipmentService->updateTrackingInformation(
        shipment: $this->shipment,
        data: ['carrier' => 'Snapshot Verifier'],
        admin: $this->admin
    );

    $freshSnapshot = $this->shipment->fresh()->items_snapshot;
    expect($freshSnapshot[0]['product_name'])->toBe('Fiddle Leaf Fig')
        ->and($freshSnapshot[0]['sku'])->toBe('FID-FIG-01')
        ->and($freshSnapshot[0]['price'])->toBe('1250.00');
});
