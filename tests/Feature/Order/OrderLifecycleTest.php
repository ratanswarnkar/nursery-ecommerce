<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Enums\StockMovementType;
use App\Exceptions\Order\InvalidOrderStatusTransitionException;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Order\OrderLifecycleService;
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
        'name' => 'Meera Patel',
        'phone' => '+919876543210',
        'email' => 'meera@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    // 3. Address
    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Meera Patel',
        'phone' => '+919876543210',
        'address_line_1' => 'Mann Enclave, near Gurukul',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
        'is_default' => true,
    ]);

    // 4. Product & Variant & Inventory
    $this->product = Product::factory()->create([
        'name' => 'Fiddle Leaf Fig',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'FLF-LRG-01',
        'price' => 1200.00,
        'is_active' => true,
    ]);

    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);

    // 5. Order with items and initial deducted stock
    $this->order = Order::create([
        'order_number' => 'ORD-20260907-LC001',
        'customer_id' => $this->customer->id,
        'customer_name' => 'Meera Patel',
        'customer_phone' => '+919876543210',
        'customer_email' => 'meera@example.com',
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 2400.00,
        'tax_amount' => 120.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 0.00,
        'grand_total' => 2520.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    $this->orderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => $this->product->name,
        'variant_name' => 'Large Ceramic Pot',
        'sku' => $this->variant->sku,
        'price' => 1200.00,
        'quantity' => 2,
        'subtotal' => 2400.00,
        'tax_amount' => 120.00,
        'discount_amount' => 0.00,
        'total' => 2520.00,
    ]);

    // Simulate initial outbound stock deduction recorded during checkout
    $this->inventory->update(['quantity' => 8]);
    StockMovement::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'type' => StockMovementType::OUTBOUND,
        'quantity' => -2,
        'previous_quantity' => 10,
        'new_quantity' => 8,
        'reference_type' => Order::class,
        'reference_id' => $this->order->id,
        'notes' => "Order #{$this->order->order_number} fulfillment deduction",
    ]);

    $this->lifecycleService = app(OrderLifecycleService::class);
});

/*
|--------------------------------------------------------------------------
| 1. OrderStatus State Machine Tests
|--------------------------------------------------------------------------
*/

test('order status enum validates permitted forward and cancellation transitions', function () {
    // PENDING can transition to PROCESSING or CANCELLED (and CONFIRMED)
    expect(OrderStatus::PENDING->canTransitionTo(OrderStatus::PROCESSING))->toBeTrue()
        ->and(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED))->toBeTrue()
        ->and(OrderStatus::PENDING->canTransitionTo(OrderStatus::CONFIRMED))->toBeTrue()
        ->and(OrderStatus::PENDING->canTransitionTo(OrderStatus::SHIPPED))->toBeFalse()
        ->and(OrderStatus::PENDING->canTransitionTo(OrderStatus::DELIVERED))->toBeFalse();

    // PROCESSING can transition to SHIPPED or CANCELLED
    expect(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::SHIPPED))->toBeTrue()
        ->and(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::CANCELLED))->toBeTrue()
        ->and(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::PENDING))->toBeFalse()
        ->and(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::DELIVERED))->toBeFalse();

    // SHIPPED can transition to OUT_FOR_DELIVERY or DELIVERED (CANNOT be cancelled)
    expect(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::OUT_FOR_DELIVERY))->toBeTrue()
        ->and(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::DELIVERED))->toBeTrue()
        ->and(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::CANCELLED))->toBeFalse()
        ->and(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::PROCESSING))->toBeFalse();

    // OUT_FOR_DELIVERY can transition to DELIVERED
    expect(OrderStatus::OUT_FOR_DELIVERY->canTransitionTo(OrderStatus::DELIVERED))->toBeTrue()
        ->and(OrderStatus::OUT_FOR_DELIVERY->canTransitionTo(OrderStatus::CANCELLED))->toBeFalse();

    // Same state is always allowed (idempotent no-op)
    expect(OrderStatus::PENDING->canTransitionTo(OrderStatus::PENDING))->toBeTrue()
        ->and(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::DELIVERED))->toBeTrue();
});

test('order status enum enforces DELIVERED and CANCELLED as strict terminal states', function () {
    // DELIVERED is terminal: cannot move to PROCESSING, CANCELLED, or RETURNED in Phase 6.3
    expect(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PROCESSING))->toBeFalse()
        ->and(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CANCELLED))->toBeFalse()
        ->and(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::RETURNED))->toBeFalse()
        ->and(OrderStatus::DELIVERED->isTerminal())->toBeTrue();

    // CANCELLED is terminal: cannot transition anywhere
    expect(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PROCESSING))->toBeFalse()
        ->and(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::DELIVERED))->toBeFalse()
        ->and(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PENDING))->toBeFalse()
        ->and(OrderStatus::CANCELLED->isTerminal())->toBeTrue();

    // Active states are not terminal
    expect(OrderStatus::PENDING->isTerminal())->toBeFalse()
        ->and(OrderStatus::PROCESSING->isTerminal())->toBeFalse()
        ->and(OrderStatus::SHIPPED->isTerminal())->toBeFalse()
        ->and(OrderStatus::OUT_FOR_DELIVERY->isTerminal())->toBeFalse();
});

test('availableTransitions returns expected targets for each state', function () {
    $pendingTransitions = OrderStatus::PENDING->availableTransitions();
    expect($pendingTransitions)->toContain(OrderStatus::PROCESSING)
        ->and($pendingTransitions)->toContain(OrderStatus::CANCELLED)
        ->and($pendingTransitions)->not->toContain(OrderStatus::DELIVERED);

    $deliveredTransitions = OrderStatus::DELIVERED->availableTransitions();
    expect($deliveredTransitions)->toBeEmpty();

    $cancelledTransitions = OrderStatus::CANCELLED->availableTransitions();
    expect($cancelledTransitions)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| 2. OrderLifecycleService Transitions & Rejections
|--------------------------------------------------------------------------
*/

test('lifecycle service transitions order forward cleanly through operational path', function () {
    // 1. PENDING -> PROCESSING
    $order = $this->lifecycleService->transitionStatus(
        $this->order,
        OrderStatus::PROCESSING,
        'Payment confirmed by payment gateway.'
    );

    expect($order->status)->toBe(OrderStatus::PROCESSING);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::PENDING->value,
        'to_status' => OrderStatus::PROCESSING->value,
        'comment' => 'Payment confirmed by payment gateway.',
    ]);

    // 2. PROCESSING -> SHIPPED
    $order = $this->lifecycleService->transitionStatus(
        $order,
        OrderStatus::SHIPPED,
        'Dispatched via nursery express delivery.'
    );

    expect($order->status)->toBe(OrderStatus::SHIPPED);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::PROCESSING->value,
        'to_status' => OrderStatus::SHIPPED->value,
    ]);

    // 3. SHIPPED -> DELIVERED
    $order = $this->lifecycleService->transitionStatus(
        $order,
        OrderStatus::DELIVERED,
        'Package received by customer.'
    );

    expect($order->status)->toBe(OrderStatus::DELIVERED);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::SHIPPED->value,
        'to_status' => OrderStatus::DELIVERED->value,
    ]);
});

test('lifecycle service throws InvalidOrderStatusTransitionException on illegal transitions', function () {
    // Direct PENDING -> DELIVERED is illegal
    expect(fn () => $this->lifecycleService->transitionStatus($this->order, OrderStatus::DELIVERED))
        ->toThrow(InvalidOrderStatusTransitionException::class);

    // Transition to DELIVERED properly first
    $this->order->update(['status' => OrderStatus::DELIVERED]);

    // DELIVERED -> PROCESSING is illegal
    expect(fn () => $this->lifecycleService->transitionStatus($this->order, OrderStatus::PROCESSING))
        ->toThrow(InvalidOrderStatusTransitionException::class);

    // DELIVERED -> CANCELLED is illegal
    expect(fn () => $this->lifecycleService->transitionStatus($this->order, OrderStatus::CANCELLED))
        ->toThrow(InvalidOrderStatusTransitionException::class);
});

test('lifecycle service handles duplicate same-status transition as idempotent no-op', function () {
    $initialHistoriesCount = OrderStatusHistory::where('order_id', $this->order->id)->count();

    // Request transition from PENDING to PENDING
    $order = $this->lifecycleService->transitionStatus($this->order, OrderStatus::PENDING);

    expect($order->status)->toBe(OrderStatus::PENDING)
        ->and(OrderStatusHistory::where('order_id', $this->order->id)->count())->toBe($initialHistoriesCount);
});

/*
|--------------------------------------------------------------------------
| 3. Inventory Release Safety on Cancellation
|--------------------------------------------------------------------------
*/

test('order cancellation restores deducted inventory and appends RELEASE movement', function () {
    expect($this->inventory->fresh()->quantity)->toBe(8);

    // Cancel order
    $this->lifecycleService->cancelOrder($this->order, 'Customer requested cancellation before dispatch.');

    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::CANCELLED);

    // Inventory restored: 8 + 2 = 10
    expect($this->inventory->fresh()->quantity)->toBe(10);

    // RELEASE stock movement created
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'type' => StockMovementType::RELEASE->value,
        'quantity' => 2,
        'previous_quantity' => 8,
        'new_quantity' => 10,
        'reference_type' => Order::class,
        'reference_id' => $this->order->id,
    ]);

    // OrderCancellation record created
    $this->assertDatabaseHas('order_cancellations', [
        'order_id' => $this->order->id,
        'status' => 'approved',
        'reason' => 'Customer requested cancellation before dispatch.',
    ]);
});

test('order cancellation does not double-release stock if cancelled multiple times', function () {
    // First cancellation
    $this->lifecycleService->cancelOrder($this->order, 'Reason 1');
    expect($this->inventory->fresh()->quantity)->toBe(10);

    $releaseMovementsCount = StockMovement::where('type', StockMovementType::RELEASE)
        ->where('reference_id', $this->order->id)
        ->count();
    expect($releaseMovementsCount)->toBe(1);

    // Second call on already cancelled order (no-op via idempotency)
    $this->lifecycleService->cancelOrder($this->order, 'Reason 2');

    // Quantity remains exactly 10, not 12
    expect($this->inventory->fresh()->quantity)->toBe(10);

    // Release movement count remains 1
    expect(StockMovement::where('type', StockMovementType::RELEASE)->where('reference_id', $this->order->id)->count())->toBe(1);
});

test('transitioning to SHIPPED or DELIVERED does not touch inventory', function () {
    $this->order->update(['status' => OrderStatus::PROCESSING]);

    $initialMovementsCount = StockMovement::where('reference_id', $this->order->id)->count();
    $initialStock = $this->inventory->fresh()->quantity;

    $this->lifecycleService->transitionStatus($this->order, OrderStatus::SHIPPED);
    expect($this->inventory->fresh()->quantity)->toBe($initialStock)
        ->and(StockMovement::where('reference_id', $this->order->id)->count())->toBe($initialMovementsCount);

    $this->lifecycleService->transitionStatus($this->order, OrderStatus::DELIVERED);
    expect($this->inventory->fresh()->quantity)->toBe($initialStock)
        ->and(StockMovement::where('reference_id', $this->order->id)->count())->toBe($initialMovementsCount);
});

/*
|--------------------------------------------------------------------------
| 4. Payment / Order Decoupling Tests
|--------------------------------------------------------------------------
*/

test('order status transitions do not alter payment status', function () {
    $this->order->update([
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PAID,
    ]);

    $this->lifecycleService->transitionStatus($this->order, OrderStatus::PROCESSING);
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);

    $this->lifecycleService->transitionStatus($this->order, OrderStatus::SHIPPED);
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);

    $this->lifecycleService->transitionStatus($this->order, OrderStatus::DELIVERED);
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

/*
|--------------------------------------------------------------------------
| 5. Admin RBAC Authorization & Controller Tests
|--------------------------------------------------------------------------
*/

test('admin with orders.update can transition order status via controller', function () {
    Permission::findOrCreate('orders.update', 'admin');
    Permission::findOrCreate('orders.view', 'admin');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['orders.view', 'orders.update']);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->post(route('admin.orders.update-status', $this->order), [
        'status' => 'processing',
        'comment' => 'Moved to processing by warehouse team.',
    ]);

    $response->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');

    expect($this->order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

test('admin with orders.cancel can cancel order via controller', function () {
    Permission::findOrCreate('orders.cancel', 'admin');
    Permission::findOrCreate('orders.view', 'admin');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['orders.view', 'orders.cancel']);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->post(route('admin.orders.cancel', $this->order), [
        'reason' => 'Customer changed delivery address location.',
    ]);

    $response->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');

    expect($this->order->fresh()->status)->toBe(OrderStatus::CANCELLED);
});

test('admin without orders.update is forbidden from updating order status', function () {
    Permission::findOrCreate('orders.view', 'admin');

    $readOnlyAdmin = Admin::factory()->create();
    $readOnlyAdmin->givePermissionTo('orders.view'); // Only view, no update

    $this->actingAs($readOnlyAdmin, 'admin');
    session(['admin_auth_token_version' => $readOnlyAdmin->auth_token_version]);

    $response = $this->post(route('admin.orders.update-status', $this->order), [
        'status' => 'processing',
    ]);

    $response->assertForbidden();
    expect($this->order->fresh()->status)->toBe(OrderStatus::PENDING);
});

test('admin without orders.cancel is forbidden from cancelling order', function () {
    Permission::findOrCreate('orders.update', 'admin');
    Permission::findOrCreate('orders.view', 'admin');

    $staffAdmin = Admin::factory()->create();
    $staffAdmin->givePermissionTo(['orders.view', 'orders.update']); // Has update, but NO cancel

    $this->actingAs($staffAdmin, 'admin');
    session(['admin_auth_token_version' => $staffAdmin->auth_token_version]);

    $response = $this->post(route('admin.orders.cancel', $this->order), [
        'reason' => 'Unauthorized cancel attempt.',
    ]);

    $response->assertForbidden();
    expect($this->order->fresh()->status)->toBe(OrderStatus::PENDING);
});

test('admin show page displays available transitions and terminal state notice', function () {
    Permission::findOrCreate('orders.view', 'admin');
    Permission::findOrCreate('orders.update', 'admin');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['orders.view', 'orders.update']);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    // Active order shows transitions
    $response = $this->get(route('admin.orders.show', $this->order));
    $response->assertOk()
        ->assertSee('Order Lifecycle Management')
        ->assertSee('Transition to Status:');

    // Terminal order shows terminal message
    $this->order->update(['status' => OrderStatus::DELIVERED]);
    $terminalResponse = $this->get(route('admin.orders.show', $this->order));
    $terminalResponse->assertOk()
        ->assertSee('Terminal Status')
        ->assertSee('This order is in terminal fulfillment state');
});

/*
|--------------------------------------------------------------------------
| 6. Customer Experience & Strict IDOR Protection Tests
|--------------------------------------------------------------------------
*/

test('customer can view own order details with status badge and timeline', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.orders.show', $this->order->order_number));

    $response->assertOk()
        ->assertSee('#'.$this->order->order_number)
        ->assertSee('Fiddle Leaf Fig')
        ->assertSee('pending')
        ->assertSee('Payment: pending')
        ->assertSee('₹2,520.00');
});

test('customer cannot access another customer order (strict IDOR protection returns 404)', function () {
    $otherCustomer = Customer::factory()->create([
        'phone' => '+919800011122',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($otherCustomer, 'customer');
    session(['customer_auth_token_version' => $otherCustomer->auth_token_version]);

    // Attempting to access Meera's order returns 404
    $response = $this->get(route('account.orders.show', $this->order->order_number));
    $response->assertNotFound();
});

test('customer cannot modify order status or access admin status endpoints', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Customer POST to admin update-status route gets redirected to admin login or rejected
    $response = $this->post(route('admin.orders.update-status', $this->order), [
        'status' => 'delivered',
    ]);

    expect($response->status())->toBeIn([302, 401, 403]);
    expect($this->order->fresh()->status)->toBe(OrderStatus::PENDING);
});
