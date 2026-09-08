<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Exceptions\Order\InvalidOrderStatusTransitionException;
use App\Exceptions\Shipping\IneligibleForShipmentException;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Invoice\InvoiceService;
use App\Services\Shipping\ShipmentService;
use Illuminate\Support\Facades\DB;
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
        'order_number' => 'ORD-20260908-SHP01',
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
});

/*
|--------------------------------------------------------------------------
| 1. Shipment Service & Core Domain Operations
|--------------------------------------------------------------------------
*/

test('1. eligible PAID + PROCESSING order can create shipment storing all metadata', function () {
    $data = [
        'carrier' => 'BlueDart Express',
        'tracking_number' => 'BD987654321IN',
        'tracking_url' => 'https://track.bluedart.com/tracking?ref=BD987654321IN',
        'estimated_delivery_at' => '2026-09-12 18:00:00',
        'notes' => 'Handle with care: fragile live nursery plants.',
    ];

    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: $data,
        admin: $this->admin
    );

    expect($shipment)->toBeInstanceOf(Shipment::class)
        ->and($shipment->carrier)->toBe('BlueDart Express')
        ->and($shipment->tracking_number)->toBe('BD987654321IN')
        ->and($shipment->tracking_url)->toBe('https://track.bluedart.com/tracking?ref=BD987654321IN')
        ->and($shipment->estimated_delivery_at?->format('Y-m-d H:i:s'))->toBe('2026-09-12 18:00:00')
        ->and($shipment->notes)->toBe('Handle with care: fragile live nursery plants.')
        ->and($shipment->shipping_status)->toBe(ShippingStatus::FULFILLED)
        ->and($shipment->shipped_at)->not->toBeNull()
        ->and($shipment->delivered_at)->toBeNull();
});

test('2. shipment items_snapshot uses authoritative OrderItem historical data', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'Delhivery'],
        admin: $this->admin
    );

    expect($shipment->items_snapshot)->toBeArray()->toHaveCount(1);

    $snap = $shipment->items_snapshot[0];
    expect($snap['order_item_id'])->toBe($this->orderItem->id)
        ->and($snap['product_name'])->toBe('Fiddle Leaf Fig')
        ->and($snap['variant_name'])->toBe('Large (12 inch pot)')
        ->and($snap['sku'])->toBe('FID-FIG-01')
        ->and($snap['quantity'])->toBe(2)
        ->and($snap['price'])->toBe('1250.00');
});

test('3. live Product changes do not alter stored shipment snapshot', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'Delhivery'],
        admin: $this->admin
    );

    // Mutate live product and variant
    $this->product->update(['name' => 'Altered Fig Name']);
    $this->variant->update(['sku' => 'MUTATED-SKU', 'price' => 9999.00]);

    $freshShipment = $shipment->fresh();
    expect($freshShipment->items_snapshot[0]['product_name'])->toBe('Fiddle Leaf Fig')
        ->and($freshShipment->items_snapshot[0]['sku'])->toBe('FID-FIG-01')
        ->and($freshShipment->items_snapshot[0]['price'])->toBe('1250.00');
});

test('4. shipment creation transitions order PROCESSING -> SHIPPED and updates shipping_status', function () {
    $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'DTDC', 'tracking_number' => 'DTDC12345'],
        admin: $this->admin
    );

    $freshOrder = $this->order->fresh();
    expect($freshOrder->status)->toBe(OrderStatus::SHIPPED)
        ->and($freshOrder->shipping_status)->toBe(ShippingStatus::FULFILLED);

    // Verify status history audit
    $history = $freshOrder->statusHistories()->latest('id')->first();
    expect($history->to_status)->toBe(OrderStatus::SHIPPED)
        ->and($history->comment)->toContain('DTDC12345');
});

test('5. shipment creation does NOT touch physical inventory', function () {
    $initialStock = $this->inventory->fresh()->quantity;
    $initialMovements = StockMovement::count();

    $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    expect($this->inventory->fresh()->quantity)->toBe($initialStock)
        ->and(StockMovement::count())->toBe($initialMovements);
});

test('6. duplicate shipment creation is strictly prevented', function () {
    $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    expect(fn () => $this->shipmentService->createShipment(
        order: $this->order->fresh(),
        data: ['carrier' => 'Delhivery'],
        admin: $this->admin
    ))->toThrow(IneligibleForShipmentException::class);
});

test('7. unpaid order cannot be dispatched', function () {
    $unpaidOrder = Order::factory()->create([
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
    ]);

    expect(fn () => $this->shipmentService->createShipment(
        order: $unpaidOrder,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    ))->toThrow(IneligibleForShipmentException::class);
});

test('8. non-PROCESSING order cannot be dispatched', function () {
    $confirmedOrder = Order::factory()->create([
        'status' => OrderStatus::CONFIRMED,
        'payment_status' => PaymentStatus::PAID,
        'shipping_status' => ShippingStatus::UNFULFILLED,
    ]);

    expect(fn () => $this->shipmentService->createShipment(
        order: $confirmedOrder,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    ))->toThrow(IneligibleForShipmentException::class);
});

test('9. cancelled or terminal order cannot be dispatched', function () {
    $cancelledOrder = Order::factory()->create([
        'status' => OrderStatus::CANCELLED,
        'payment_status' => PaymentStatus::CANCELLED,
        'shipping_status' => ShippingStatus::UNFULFILLED,
    ]);

    expect(fn () => $this->shipmentService->createShipment(
        order: $cancelledOrder,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    ))->toThrow(IneligibleForShipmentException::class);
});

/*
|--------------------------------------------------------------------------
| 2. Milestone Transitions (Out for Delivery & Delivered)
|--------------------------------------------------------------------------
*/

test('10. SHIPPED -> OUT_FOR_DELIVERY works smoothly without inventory side effects', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    $initialStock = $this->inventory->fresh()->quantity;

    $this->shipmentService->markOutForDelivery(
        shipment: $shipment,
        comment: 'Driver out for local delivery in North Delhi.',
        admin: $this->admin
    );

    expect($this->order->fresh()->status)->toBe(OrderStatus::OUT_FOR_DELIVERY)
        ->and($this->order->fresh()->shipping_status)->toBe(ShippingStatus::FULFILLED)
        ->and($this->inventory->fresh()->quantity)->toBe($initialStock);
});

test('11. OUT_FOR_DELIVERY -> DELIVERED records delivered_at timestamp', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    $this->shipmentService->markOutForDelivery($shipment, admin: $this->admin);

    $this->shipmentService->markDelivered(
        shipment: $shipment,
        comment: 'Delivered to resident at door.',
        admin: $this->admin
    );

    $freshOrder = $this->order->fresh();
    $freshShipment = $shipment->fresh();

    expect($freshOrder->status)->toBe(OrderStatus::DELIVERED)
        ->and($freshOrder->shipping_status)->toBe(ShippingStatus::FULFILLED)
        ->and($freshShipment->delivered_at)->not->toBeNull();
});

test('12. direct SHIPPED -> DELIVERED transition works and records delivered_at', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'Delhivery'],
        admin: $this->admin
    );

    $this->shipmentService->markDelivered(
        shipment: $shipment,
        comment: 'Direct courier delivery recorded.',
        admin: $this->admin
    );

    expect($this->order->fresh()->status)->toBe(OrderStatus::DELIVERED)
        ->and($shipment->fresh()->delivered_at)->not->toBeNull();
});

test('13. invalid lifecycle transition from DELIVERED is strictly rejected', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    $this->shipmentService->markDelivered($shipment, admin: $this->admin);

    expect(fn () => $this->shipmentService->markOutForDelivery($shipment, admin: $this->admin))
        ->toThrow(InvalidOrderStatusTransitionException::class);
});

test('14. audit logs are recorded for shipment created, out for delivery, and delivered', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart', 'tracking_number' => 'BD101010'],
        admin: $this->admin
    );

    $this->shipmentService->markOutForDelivery($shipment, admin: $this->admin);
    $this->shipmentService->markDelivered($shipment, admin: $this->admin);

    expect(AuditLog::where('action', 'order.shipment_created')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'order.shipment_out_for_delivery')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'order.shipment_delivered')->exists())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 3. Invoice Isolation
|--------------------------------------------------------------------------
*/

test('15. invoice remains completely immutable and untouched throughout shipment lifecycle', function () {
    $invoiceService = app(InvoiceService::class);
    $invoice = $invoiceService->getOrCreateInvoiceForOrder($this->order);
    $initialSnapshot = $invoice->items_snapshot;
    $initialInvoiceDate = $invoice->invoice_date;

    // Create and progress shipment
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'Delhivery', 'tracking_number' => 'DL12345'],
        admin: $this->admin
    );
    $this->shipmentService->markDelivered($shipment, admin: $this->admin);

    $freshInvoice = $invoice->fresh();
    expect($freshInvoice->invoice_number)->toBe($invoice->invoice_number)
        ->and($freshInvoice->invoice_date->equalTo($initialInvoiceDate))->toBeTrue()
        ->and($freshInvoice->items_snapshot)->toEqual($initialSnapshot)
        ->and($freshInvoice->grand_total)->toBe($invoice->grand_total);
});

/*
|--------------------------------------------------------------------------
| 4. Admin Controller & Authorization Tests
|--------------------------------------------------------------------------
*/

test('16. authorized admin with orders.update can dispatch shipment via HTTP endpoint', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->post(route('admin.orders.shipments.create', $this->order), [
        'carrier' => 'SpeedPost India',
        'tracking_number' => 'SP123456789IN',
        'tracking_url' => 'https://www.indiapost.gov.in/track?id=SP123456789IN',
        'estimated_delivery_at' => '2026-09-14',
        'notes' => 'Dispatched from greenhouse nursery hub.',
    ]);

    $response->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');

    expect($this->order->fresh()->status)->toBe(OrderStatus::SHIPPED)
        ->and($this->order->shipments()->count())->toBe(1);

    $shipment = $this->order->shipments()->first();
    expect($shipment->carrier)->toBe('SpeedPost India')
        ->and($shipment->tracking_number)->toBe('SP123456789IN')
        ->and($shipment->tracking_url)->toBe('https://www.indiapost.gov.in/track?id=SP123456789IN');
});

test('17. unauthorized admin without orders.update receives 403 Forbidden', function () {
    $readOnlyAdmin = Admin::factory()->create(['auth_token_version' => 1]);
    $readOnlyAdmin->givePermissionTo('orders.view'); // Only view

    $this->actingAs($readOnlyAdmin, 'admin');
    session(['admin_auth_token_version' => $readOnlyAdmin->auth_token_version]);

    $response = $this->post(route('admin.orders.shipments.create', $this->order), [
        'carrier' => 'BlueDart',
    ]);

    $response->assertForbidden();
    expect($this->order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

test('18. invalid tracking URL is rejected with validation error', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->post(route('admin.orders.shipments.create', $this->order), [
        'carrier' => 'BlueDart',
        'tracking_url' => 'not-a-valid-url',
    ]);

    $response->assertSessionHasErrors('tracking_url');
    expect($this->order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

test('19. javascript or data tracking URL schemes are strictly rejected', function () {
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // JavaScript pseudo-protocol
    $jsResponse = $this->post(route('admin.orders.shipments.create', $this->order), [
        'carrier' => 'BlueDart',
        'tracking_url' => 'javascript:alert(1)',
    ]);
    $jsResponse->assertSessionHasErrors('tracking_url');

    // Data URI scheme
    $dataResponse = $this->post(route('admin.orders.shipments.create', $this->order), [
        'carrier' => 'BlueDart',
        'tracking_url' => 'data:text/html,<script>alert(1)</script>',
    ]);
    $dataResponse->assertSessionHasErrors('tracking_url');

    expect($this->order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

test('20. admin can progress shipment through out-for-delivery and delivered routes', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart', 'tracking_number' => 'BD555'],
        admin: $this->admin
    );

    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // Out for delivery
    $outRes = $this->post(route('admin.orders.shipments.out-for-delivery', [$this->order, $shipment]), [
        'comment' => 'Out for delivery today',
    ]);
    $outRes->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');
    expect($this->order->fresh()->status)->toBe(OrderStatus::OUT_FOR_DELIVERY);

    // Delivered
    $delivRes = $this->post(route('admin.orders.shipments.delivered', [$this->order, $shipment]), [
        'comment' => 'Successfully handed over to customer',
    ]);
    $delivRes->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');
    expect($this->order->fresh()->status)->toBe(OrderStatus::DELIVERED)
        ->and($shipment->fresh()->delivered_at)->not->toBeNull();
});

test('21. mismatched order and shipment route IDs returns 404', function () {
    $otherOrder = Order::factory()->create([
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
    ]);

    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // Attempt to access shipment under the wrong order ID
    $response = $this->post(route('admin.orders.shipments.delivered', [$otherOrder, $shipment]));
    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| 5. Customer Order Experience & Strict IDOR Tests
|--------------------------------------------------------------------------
*/

test('22. customer can view own shipment information on order detail page', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: [
            'carrier' => 'Delhivery Express',
            'tracking_number' => 'DEL123456789',
            'tracking_url' => 'https://www.delhivery.com/track?id=DEL123456789',
            'estimated_delivery_at' => '2026-09-13',
        ],
        admin: $this->admin
    );

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.orders.show', $this->order->order_number));

    $response->assertOk()
        ->assertSee('Shipment & Delivery Details', false)
        ->assertSee('Delhivery Express')
        ->assertSee('DEL123456789')
        ->assertSee('https://www.delhivery.com/track?id=DEL123456789')
        ->assertSee('Track Package Online');
});

test('23. customer cannot access another customer order or shipment details (strict IDOR)', function () {
    $otherCustomer = Customer::factory()->create(['auth_token_version' => 1]);

    $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'Secret Courier', 'tracking_number' => 'SEC999'],
        admin: $this->admin
    );

    // Other customer tries to view Aditi's order
    $this->actingAs($otherCustomer, 'customer');
    session(['customer_auth_token_version' => $otherCustomer->auth_token_version]);

    $response = $this->get(route('account.orders.show', $this->order->order_number));
    $response->assertNotFound();
});

test('24. admin order detail view displays fulfillment section and active shipment info', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: [
            'carrier' => 'BlueDart Express',
            'tracking_number' => 'BD777777',
            'tracking_url' => 'https://track.bluedart.com',
        ],
        admin: $this->admin
    );

    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->get(route('admin.orders.show', $this->order));

    $response->assertOk()
        ->assertSee('Fulfillment & Shipping', false)
        ->assertSee('BlueDart Express')
        ->assertSee('BD777777')
        ->assertSee('Out for Delivery')
        ->assertSee('Mark Delivered');
});

test('25. concurrent shipment creation is safely prevented inside database transaction lock', function () {
    $executedFirst = false;
    $caughtExceptionInSecond = false;

    // Simulate two simultaneous execution contexts on the same order
    DB::transaction(function () use (&$executedFirst, &$caughtExceptionInSecond) {
        $this->shipmentService->createShipment(
            order: $this->order,
            data: ['carrier' => 'First Courier', 'tracking_number' => 'FC101'],
            admin: $this->admin
        );
        $executedFirst = true;

        try {
            // Second attempt while first is in transaction / fresh state
            $this->shipmentService->createShipment(
                order: $this->order,
                data: ['carrier' => 'Second Courier', 'tracking_number' => 'SC202'],
                admin: $this->admin
            );
        } catch (IneligibleForShipmentException $e) {
            $caughtExceptionInSecond = true;
        }
    });

    expect($executedFirst)->toBeTrue()
        ->and($caughtExceptionInSecond)->toBeTrue()
        ->and($this->order->shipments()->count())->toBe(1);
});

test('26. inventory quantities and stock movements remain strictly unchanged through full shipment lifecycle', function () {
    $stockBefore = $this->inventory->fresh()->quantity;
    $movementsBefore = StockMovement::count();

    // Step 1: Create shipment
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'Delhivery'],
        admin: $this->admin
    );

    expect($this->inventory->fresh()->quantity)->toBe($stockBefore)
        ->and(StockMovement::count())->toBe($movementsBefore);

    // Step 2: Mark Out for Delivery
    $this->shipmentService->markOutForDelivery($shipment, admin: $this->admin);

    expect($this->inventory->fresh()->quantity)->toBe($stockBefore)
        ->and(StockMovement::count())->toBe($movementsBefore);

    // Step 3: Mark Delivered
    $this->shipmentService->markDelivered($shipment, admin: $this->admin);

    expect($this->inventory->fresh()->quantity)->toBe($stockBefore)
        ->and(StockMovement::count())->toBe($movementsBefore);
});

test('27. delivered_at timestamp is accurately recorded upon delivery', function () {
    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: ['carrier' => 'BlueDart'],
        admin: $this->admin
    );

    expect($shipment->delivered_at)->toBeNull();

    $beforeDelivery = now()->subSecond();
    $this->shipmentService->markDelivered($shipment, admin: $this->admin);
    $afterDelivery = now()->addSecond();

    $freshShipment = $shipment->fresh();
    expect($freshShipment->delivered_at)->not->toBeNull()
        ->and($freshShipment->delivered_at->between($beforeDelivery, $afterDelivery))->toBeTrue();
});

test('28. customer and admin views properly escape carrier, tracking number, and notes against XSS', function () {
    $xssPayload = '<script>alert("xss")</script>';

    $shipment = $this->shipmentService->createShipment(
        order: $this->order,
        data: [
            'carrier' => $xssPayload,
            'tracking_number' => $xssPayload,
            'notes' => $xssPayload,
        ],
        admin: $this->admin
    );

    // Customer view
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);
    $custResponse = $this->get(route('account.orders.show', $this->order->order_number));

    $custResponse->assertOk()
        ->assertDontSee($xssPayload, false)
        ->assertSee(e($xssPayload), false);

    // Admin view
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
    $adminResponse = $this->get(route('admin.orders.show', $this->order));

    $adminResponse->assertOk()
        ->assertDontSee($xssPayload, false)
        ->assertSee(e($xssPayload), false);
});
