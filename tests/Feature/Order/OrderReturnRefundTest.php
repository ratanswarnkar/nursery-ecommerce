<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ShippingStatus;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Invoice\InvoiceService;
use App\Services\Order\OrderReturnService;
use App\Services\Payment\Gateways\RazorpayPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use Razorpay\Api\Api;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Ensure permissions exist
    Permission::findOrCreate('orders.view', 'admin');
    Permission::findOrCreate('orders.update', 'admin');
    Permission::findOrCreate('orders.cancel', 'admin');

    $this->admin = Admin::factory()->create([
        'name' => 'Store Operations Admin',
        'email' => 'admin_ops@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);
    $this->admin->givePermissionTo(['orders.view', 'orders.update']);

    $this->unauthorizedAdmin = Admin::factory()->create([
        'name' => 'Read Only Admin',
        'email' => 'admin_ro@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);
    $this->unauthorizedAdmin->givePermissionTo(['orders.view']); // no orders.update

    $this->customer = Customer::factory()->create([
        'name' => 'Pooja Verma',
        'phone' => '+919811199999',
        'email' => 'pooja@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    $this->otherCustomer = Customer::factory()->create([
        'name' => 'Rahul Sharma',
        'phone' => '+919822288888',
        'email' => 'rahul@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::firstOrCreate(
        ['code' => 'DEL-HUB-01'],
        [
            'name' => 'Main Botanical Nursery Hub',
            'is_active' => true,
            'is_default' => true,
        ]
    );

    $this->product = Product::factory()->create([
        'name' => 'Golden Pothos Money Plant',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'SKU-POTHOS-CRM-01',
        'price' => 500.00,
        'is_active' => true,
    ]);

    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 0,
    ]);

    // Create Delivered & Paid order
    $this->order = Order::create([
        'order_number' => 'ORD-RET-TEST-001',
        'customer_id' => $this->customer->id,
        'customer_name' => 'Pooja Verma',
        'customer_phone' => '+919811199999',
        'customer_email' => 'pooja@example.com',
        'status' => OrderStatus::DELIVERED,
        'payment_status' => PaymentStatus::PAID,
        'shipping_status' => ShippingStatus::FULFILLED,
        'currency' => 'INR',
        'subtotal' => 1000.00,
        'tax_amount' => 0.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 0.00,
        'grand_total' => 1000.00,
        'shipping_address_json' => [
            'recipient_name' => 'Pooja Verma',
            'phone' => '+919811199999',
            'address_line_1' => 'Flat 202, Delhi',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
        'billing_address_json' => [
            'recipient_name' => 'Pooja Verma',
            'phone' => '+919811199999',
            'address_line_1' => 'Flat 202, Delhi',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
    ]);

    $this->orderItem = OrderItem::create([
        'order_id' => $this->order->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => 'Golden Pothos Money Plant',
        'variant_name' => 'Ceramic Planter',
        'sku' => 'SKU-POTHOS-CRM-01',
        'price' => 500.00,
        'quantity' => 2,
        'subtotal' => 1000.00,
        'tax_amount' => 0.00,
        'discount_amount' => 0.00,
        'total' => 1000.00,
    ]);

    $this->paymentService = app(PaymentService::class);
    $this->orderReturnService = app(OrderReturnService::class);
    $this->inventoryService = app(InventoryService::class);
    $this->invoiceService = app(InvoiceService::class);
});

/*
|--------------------------------------------------------------------------
| 1. Customer Return Eligibility & Submission Tests
|--------------------------------------------------------------------------
*/

test('1. Eligible customer can request return for delivered and paid order', function () {
    expect($this->orderReturnService->canRequestReturn($this->order))->toBeTrue();

    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->actingAs($this->customer, 'customer')
        ->post(route('account.orders.return.store', $this->order->order_number), [
            'reason' => 'Transit damage on foliage and broken ceramic pot.',
            'items' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'quantity' => 1,
                ],
            ],
        ]);

    $response->assertRedirect(route('account.orders.show', $this->order->order_number))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('order_returns', [
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 1,
        'status' => ReturnStatus::REQUESTED->value,
        'refund_amount' => '500.00',
    ]);
});

test('2. Customer cannot access another customer order (strict IDOR protection)', function () {
    session(['customer_auth_token_version' => $this->otherCustomer->auth_token_version]);

    // Attempt view return form
    $this->actingAs($this->otherCustomer, 'customer')
        ->get(route('account.orders.return', $this->order->order_number))
        ->assertNotFound();

    // Attempt submit return
    $this->actingAs($this->otherCustomer, 'customer')
        ->post(route('account.orders.return.store', $this->order->order_number), [
            'reason' => 'Unauthorized return attempt',
            'items' => [
                ['order_item_id' => $this->orderItem->id, 'quantity' => 1],
            ],
        ])
        ->assertNotFound();
});

test('3. Customer cannot submit duplicate active return when request is pending', function () {
    // Create initial return request
    OrderReturn::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 1,
        'reason' => 'Initial pending return',
        'status' => ReturnStatus::REQUESTED,
        'refund_amount' => 500.00,
    ]);

    expect($this->orderReturnService->canRequestReturn($this->order))->toBeFalse();

    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->actingAs($this->customer, 'customer')
        ->post(route('account.orders.return.store', $this->order->order_number), [
            'reason' => 'Second return attempt for same order',
            'items' => [
                ['order_item_id' => $this->orderItem->id, 'quantity' => 1],
            ],
        ]);

    $response->assertSessionHasErrors('order');
});

test('4. Invalid quantity is rejected (quantity > purchased or <= 0)', function () {
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Attempt quantity > 2 (purchased is 2)
    $responseExceed = $this->actingAs($this->customer, 'customer')
        ->post(route('account.orders.return.store', $this->order->order_number), [
            'reason' => 'Returning more than purchased',
            'items' => [
                ['order_item_id' => $this->orderItem->id, 'quantity' => 5],
            ],
        ]);

    $responseExceed->assertSessionHasErrors();

    // Attempt 0 quantity
    $responseZero = $this->actingAs($this->customer, 'customer')
        ->post(route('account.orders.return.store', $this->order->order_number), [
            'reason' => 'Returning zero items',
            'items' => [
                ['order_item_id' => $this->orderItem->id, 'quantity' => 0],
            ],
        ]);

    $responseZero->assertSessionHasErrors();
});

/*
|--------------------------------------------------------------------------
| 2. Payment Refund Processing & Gateways
|--------------------------------------------------------------------------
*/

test('5. Unpaid order cannot be refunded', function () {
    $unpaidOrder = Order::create([
        'order_number' => 'ORD-UNPAID-001',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'grand_total' => 500.00,
        'subtotal' => 500.00,
        'tax_amount' => 0.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 0.00,
        'shipping_address_json' => [
            'recipient_name' => 'Pooja Verma',
            'phone' => '+919811199999',
            'address_line_1' => 'Flat 202, Delhi',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
        'billing_address_json' => [
            'recipient_name' => 'Pooja Verma',
            'phone' => '+919811199999',
            'address_line_1' => 'Flat 202, Delhi',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
    ]);

    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    $response = $this->actingAs($this->admin, 'admin')
        ->post(route('admin.orders.refunds.process', $unpaidOrder), [
            'amount' => 500.00,
            'reason' => 'Refund test on unpaid order',
        ]);

    $response->assertRedirect(route('admin.orders.show', $unpaidOrder))
        ->assertSessionHas('error', 'No refundable payment transaction found for this order.');
});

test('6. Paid transaction can be refunded via payment service', function () {
    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-PAID-001',
        'gateway' => 'null',
        'gateway_transaction_id' => 'null_tx_12345',
        'amount' => 1000.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    $refund = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 1000.00,
        reason: 'Customer satisfaction guarantee',
        admin: $this->admin
    );

    expect($refund->status)->toBe(RefundStatus::PROCESSED)
        ->and((float) $refund->amount)->toBe(1000.00)
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::REFUNDED);
});

test('7. Partial refund works and leaves correct remaining refundable balance', function () {
    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-PARTIAL-001',
        'gateway' => 'null',
        'gateway_transaction_id' => 'null_tx_partial_1',
        'amount' => 1000.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    // Refund ₹300 of ₹1000
    $refund1 = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 300.00,
        reason: 'Partial plant damage compensation',
        admin: $this->admin
    );

    expect($refund1->status)->toBe(RefundStatus::PROCESSED)
        ->and((float) $transaction->fresh()->refundedAmount())->toBe(300.00)
        ->and((float) $transaction->fresh()->remainingRefundableAmount())->toBe(700.00)
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED);

    // Refund remaining ₹700
    $refund2 = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 700.00,
        reason: 'Final refund of remaining balance',
        admin: $this->admin
    );

    expect($refund2->status)->toBe(RefundStatus::PROCESSED)
        ->and((float) $transaction->fresh()->refundedAmount())->toBe(1000.00)
        ->and((float) $transaction->fresh()->remainingRefundableAmount())->toBe(0.00)
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::REFUNDED);
});

test('8. Refund > paid amount is rejected', function () {
    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-EXCEED-001',
        'gateway' => 'null',
        'gateway_transaction_id' => 'null_tx_exceed',
        'amount' => 1000.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    expect(fn () => $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 1001.00,
        reason: 'Illegal excessive refund',
        admin: $this->admin
    ))->toThrow(DomainException::class);
});

test('9. Refund > remaining refundable balance is rejected on partial refund', function () {
    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-OVER-PARTIAL-001',
        'gateway' => 'null',
        'gateway_transaction_id' => 'null_tx_over',
        'amount' => 1000.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    // Already refunded ₹300, remaining is ₹700
    $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 300.00,
        reason: 'First partial refund',
        admin: $this->admin
    );

    // Attempting ₹701 when only ₹700 remains
    expect(fn () => $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 701.00,
        reason: 'Over-balance refund attempt',
        admin: $this->admin
    ))->toThrow(DomainException::class);
});

test('10. Duplicate idempotency key returns existing refund and does not call gateway twice', function () {
    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-IDEMP-001',
        'gateway' => 'null',
        'gateway_transaction_id' => 'null_tx_idemp',
        'amount' => 500.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    $key = 'idemp_unique_key_12345';

    $refund1 = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 250.00,
        reason: 'Idempotent refund call 1',
        admin: $this->admin,
        idempotencyKey: $key
    );

    $refund2 = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 250.00,
        reason: 'Idempotent refund call 2 duplicate',
        admin: $this->admin,
        idempotencyKey: $key
    );

    expect($refund1->id)->toBe($refund2->id)
        ->and(OrderRefund::where('idempotency_key', $key)->count())->toBe(1);
});

test('11. Razorpay refund ID is stored and gateway transaction is authoritatively used', function () {
    $testKeyId = 'rzp_test_refundKey123';
    $testKeySecret = 'testSecretRefundSecret456';

    config([
        'services.razorpay.key_id' => $testKeyId,
        'services.razorpay.key_secret' => $testKeySecret,
    ]);

    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-RZP-REF-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => 'pay_authoritative_99999', // Authoritative Razorpay Payment ID
        'amount' => 1000.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    $mockApi = Mockery::mock(Api::class);
    $paymentMock = Mockery::mock();
    $paymentResource = Mockery::mock();

    $paymentResource->shouldReceive('refund')
        ->once()
        ->with(Mockery::on(function ($payload) {
            return $payload['amount'] === 100000; // 1000 INR = 100000 paise
        }))
        ->andReturn((object) ['id' => 'rfnd_razorpay_success_123']);

    $paymentMock->shouldReceive('fetch')
        ->once()
        ->with('pay_authoritative_99999')
        ->andReturn($paymentResource);

    $mockApi->payment = $paymentMock;

    /** @var RazorpayPaymentGateway $gateway */
    $gateway = app(PaymentGatewayManager::class)->gateway('razorpay');
    $gateway->setApi($mockApi);

    $refund = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 1000.00,
        reason: 'Verified return refund via Razorpay',
        admin: $this->admin
    );

    expect($refund->status)->toBe(RefundStatus::PROCESSED)
        ->and($refund->gateway)->toBe('razorpay')
        ->and($refund->gateway_refund_id)->toBe('rfnd_razorpay_success_123');

    $this->assertDatabaseHas('order_refunds', [
        'id' => $refund->id,
        'gateway_refund_id' => 'rfnd_razorpay_success_123',
        'gateway' => 'razorpay',
    ]);
});

test('12. Razorpay failure is handled safely without leaking credentials', function () {
    $testKeyId = 'rzp_test_refundKey123';
    $testKeySecret = 'testSecretRefundSecret456';

    config([
        'services.razorpay.key_id' => $testKeyId,
        'services.razorpay.key_secret' => $testKeySecret,
    ]);

    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-RZP-FAIL-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => 'pay_authoritative_fail_111',
        'amount' => 500.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
    ]);

    $mockApi = Mockery::mock(Api::class);
    $paymentMock = Mockery::mock();
    $paymentResource = Mockery::mock();

    $paymentResource->shouldReceive('refund')
        ->once()
        ->andThrow(new Exception('Payment balance insufficient on gateway account.'));

    $paymentMock->shouldReceive('fetch')
        ->once()
        ->with('pay_authoritative_fail_111')
        ->andReturn($paymentResource);

    $mockApi->payment = $paymentMock;

    /** @var RazorpayPaymentGateway $gateway */
    $gateway = app(PaymentGatewayManager::class)->gateway('razorpay');
    $gateway->setApi($mockApi);

    $refund = $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 500.00,
        reason: 'Testing gateway failure response',
        admin: $this->admin
    );

    expect($refund->status)->toBe(RefundStatus::FAILED)
        ->and($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);

    // Ensure secret is not in payload
    $payloadJson = json_encode($refund->payload);
    expect($payloadJson)->not->toContain($testKeySecret);
});

test('13. PAID -> PARTIALLY_REFUNDED transitions payment state properly', function () {
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::PARTIALLY_REFUNDED))->toBeTrue();
});

test('14. PARTIALLY_REFUNDED -> REFUNDED transitions payment state properly', function () {
    expect(PaymentStatus::PARTIALLY_REFUNDED->canTransitionTo(PaymentStatus::REFUNDED))->toBeTrue()
        ->and(PaymentStatus::PARTIALLY_REFUNDED->canTransitionTo(PaymentStatus::PAID))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| 3. Customer & Admin Return Flow Tests
|--------------------------------------------------------------------------
*/

test('15. Customer sees return/refund status on order show page', function () {
    $orderReturn = OrderReturn::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 1,
        'reason' => 'Damaged sapling',
        'status' => ReturnStatus::REQUESTED,
        'refund_amount' => 500.00,
    ]);

    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->actingAs($this->customer, 'customer')
        ->get(route('account.orders.show', $this->order->order_number));

    $response->assertOk()
        ->assertSee('Returns & Refunds')
        ->assertSee('requested')
        ->assertSee('Damaged sapling');
});

test('16. Admin approval is protected by orders.update RBAC', function () {
    $orderReturn = OrderReturn::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 1,
        'reason' => 'Damaged leaves',
        'status' => ReturnStatus::REQUESTED,
        'refund_amount' => 500.00,
    ]);

    session(['admin_auth_token_version' => $this->unauthorizedAdmin->auth_token_version]);

    // Unauthorized admin without orders.update gets 403
    $this->actingAs($this->unauthorizedAdmin, 'admin')
        ->post(route('admin.orders.returns.approve', [$this->order, $orderReturn]))
        ->assertForbidden();

    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // Authorized admin succeeds
    $response = $this->actingAs($this->admin, 'admin')
        ->post(route('admin.orders.returns.approve', [$this->order, $orderReturn]), [
            'admin_notes' => 'Pickup scheduled with Delhi courier.',
        ]);

    $response->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');

    expect($orderReturn->fresh()->status)->toBe(ReturnStatus::APPROVED)
        ->and($orderReturn->fresh()->admin_notes)->toBe('Pickup scheduled with Delhi courier.')
        ->and($orderReturn->fresh()->approved_at)->not->toBeNull();
});

test('17. Admin rejection requires a mandatory reason', function () {
    $orderReturn = OrderReturn::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 1,
        'reason' => 'Customer changed mind',
        'status' => ReturnStatus::REQUESTED,
        'refund_amount' => 500.00,
    ]);

    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // Missing reason fails validation
    $responseEmpty = $this->actingAs($this->admin, 'admin')
        ->post(route('admin.orders.returns.reject', [$this->order, $orderReturn]), [
            'reason' => '',
        ]);

    $responseEmpty->assertSessionHasErrors('reason');

    // Valid rejection succeeds
    $responseValid = $this->actingAs($this->admin, 'admin')
        ->post(route('admin.orders.returns.reject', [$this->order, $orderReturn]), [
            'reason' => 'Change of mind is ineligible for perishable plant return.',
        ]);

    $responseValid->assertRedirect(route('admin.orders.show', $this->order))
        ->assertSessionHas('success');

    expect($orderReturn->fresh()->status)->toBe(ReturnStatus::REJECTED)
        ->and($orderReturn->fresh()->admin_notes)->toBe('Change of mind is ineligible for perishable plant return.')
        ->and($orderReturn->fresh()->rejected_at)->not->toBeNull();
});

test('18. Unauthorized user cannot process refund', function () {
    $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-UNAUTH-001',
        'gateway' => 'null',
        'amount' => 500.00,
        'status' => PaymentStatus::PAID,
    ]);

    // Customer cannot refund
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);
    $this->actingAs($this->customer, 'customer')
        ->post(route('admin.orders.refunds.process', $this->order), [
            'amount' => 500.00,
            'reason' => 'Unauthorized customer refund attempt',
        ])
        ->assertRedirect(route('admin.login')); // Admin guard redirects unauthenticated

    // Admin without orders.update is forbidden
    session(['admin_auth_token_version' => $this->unauthorizedAdmin->auth_token_version]);
    $this->actingAs($this->unauthorizedAdmin, 'admin')
        ->post(route('admin.orders.refunds.process', $this->order), [
            'amount' => 500.00,
            'reason' => 'Unauthorized admin refund attempt',
        ])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| 4. Inventory, Restocking & Invoicing Tests
|--------------------------------------------------------------------------
*/

test('19. Refund does not deduct inventory', function () {
    $startingStock = 50;
    $this->inventory->update(['quantity' => $startingStock]);

    $transaction = $this->order->paymentTransactions()->create([
        'transaction_number' => 'TXN-STOCK-TEST-001',
        'gateway' => 'null',
        'amount' => 500.00,
        'status' => PaymentStatus::PAID,
    ]);

    $this->paymentService->processRefund(
        transaction: $transaction,
        amount: 500.00,
        reason: 'Refund without touching inventory',
        admin: $this->admin
    );

    expect($this->inventory->fresh()->quantity)->toBe($startingStock);
});

test('20. Returned stock can be restocked explicitly after return completion', function () {
    $startingStock = 20;
    $this->inventory->update(['quantity' => $startingStock]);

    $orderReturn = OrderReturn::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 2,
        'reason' => 'Physical return arrived at nursery center',
        'status' => ReturnStatus::APPROVED,
        'refund_amount' => 1000.00,
    ]);

    session(['admin_auth_token_version' => $this->admin->auth_token_version]);

    // Admin marks complete and requests restock
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.orders.returns.complete', [$this->order, $orderReturn]), [
            'restock' => 1,
        ])
        ->assertRedirect(route('admin.orders.show', $this->order));

    expect($orderReturn->fresh()->status)->toBe(ReturnStatus::COMPLETED)
        ->and($this->inventory->fresh()->quantity)->toBe($startingStock + 2);

    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $this->variant->id,
        'type' => 'inbound',
        'quantity' => 2,
        'reference_type' => OrderReturn::class,
        'reference_id' => $orderReturn->id,
    ]);
});

test('21. Restocking cannot happen twice (idempotency guard)', function () {
    $startingStock = 10;
    $this->inventory->update(['quantity' => $startingStock]);

    $orderReturn = OrderReturn::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'quantity' => 1,
        'reason' => 'Physical return test',
        'status' => ReturnStatus::COMPLETED,
        'refund_amount' => 500.00,
    ]);

    // Restock 1st time
    $this->inventoryService->restockReturnedItems($orderReturn, $this->admin);
    expect($this->inventory->fresh()->quantity)->toBe(11);

    // Attempt 2nd restock on same return
    $this->inventoryService->restockReturnedItems($orderReturn, $this->admin);
    expect($this->inventory->fresh()->quantity)->toBe(11) // Did not increment again!
        ->and(StockMovement::where('reference_type', OrderReturn::class)->where('reference_id', $orderReturn->id)->count())->toBe(1);
});

test('22. Order history and audit events are recorded for return lifecycle', function () {
    $this->orderReturnService->createReturnRequest(
        order: $this->order,
        itemsData: [['order_item_id' => $this->orderItem->id, 'quantity' => 1]],
        reason: 'Audit trail test reason',
        customer: $this->customer
    );

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $this->order->id,
        'changed_by_type' => Customer::class,
        'changed_by_id' => $this->customer->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'order.return_requested',
        'customer_id' => $this->customer->id,
    ]);
});

test('23. Invoice remains available after return and refund', function () {
    // Generate invoice first
    $invoice = $this->invoiceService->getOrCreateInvoiceForOrder($this->order);
    expect($invoice)->not->toBeNull();

    // Mark refunded
    $this->order->update(['payment_status' => PaymentStatus::REFUNDED]);

    expect($this->invoiceService->canGenerateInvoice($this->order))->toBeTrue()
        ->and(Invoice::where('order_id', $this->order->id)->exists())->toBeTrue();
});

test('24. Razorpay key secret is never exposed in response payloads or views', function () {
    $gateway = new RazorpayPaymentGateway(null, 'rzp_test_public_key', 'secret_never_leak_this_key');

    $res = $this->get(route('policy.refund'));
    $res->assertOk();
    expect($res->getContent())->not->toContain('secret_never_leak_this_key')
        ->and($res->getContent())->not->toContain('rzp_test_public_key');
});

/*
|--------------------------------------------------------------------------
| 5. Policy Pages & Business Rules Tests
|--------------------------------------------------------------------------
*/

test('25. All policy pages render correctly with strictly accurate business rules', function () {
    // 1. Shipping Policy
    $shippingRes = $this->get(route('policy.shipping'));
    $shippingRes->assertOk()
        ->assertSee('ONLY within Delhi NCR', false)
        ->assertSee('ABOVE ₹1,000', false)
        ->assertSee('within 3 days', false);

    // 2. Cancellation and Refund Policy (must NOT invent numeric day window)
    $refundRes = $this->get(route('policy.refund'));
    $refundRes->assertOk()
        ->assertSee('Cancellation, Return &amp; Refund Policy', false)
        ->assertDontSee('7 days')
        ->assertDontSee('10 days')
        ->assertDontSee('15 days')
        ->assertDontSee('30 days');

    // 3. Privacy Policy
    $this->get(route('policy.privacy'))->assertOk();

    // 4. Terms & Conditions
    $this->get(route('policy.terms'))->assertOk();

    // 5. Contact Us (verified business details)
    $contactRes = $this->get(route('policy.contact'));
    $contactRes->assertOk()
        ->assertSee('Sugandha Farms and Nursery')
        ->assertSee('Mann Enclave')
        ->assertSee('098111 14365');
});
