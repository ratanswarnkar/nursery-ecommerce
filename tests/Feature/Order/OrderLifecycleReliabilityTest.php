<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Enums\StockMovementType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\TaxRule;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Invoice\InvoiceService;
use App\Services\Order\OrderCreationService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function createReliabilityOrder(Customer $customer, array $attributes = []): Order
{
    static $seq = 100;
    $seq++;

    $addressData = [
        'recipient_name' => $customer->name,
        'phone' => $customer->phone,
        'address_line_1' => 'Mann Enclave, near Gurukul',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
    ];

    $order = Order::create(array_merge([
        'order_number' => 'ORD-REL-'.$seq,
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_phone' => $customer->phone,
        'customer_email' => $customer->email,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 600.00,
        'tax_amount' => 30.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 0.00,
        'grand_total' => 630.00,
        'shipping_address_json' => $addressData,
        'billing_address_json' => $addressData,
    ], $attributes));

    if (isset($attributes['created_at'])) {
        Order::where('id', $order->id)->update(['created_at' => $attributes['created_at']]);
        $order->refresh();
    }

    return $order;
}

function loginReliabilityCustomer($test, Customer $customer): void
{
    $test->actingAs($customer, 'customer');
    session(['customer_auth_token_version' => $customer->auth_token_version]);
}

beforeEach(function () {
    RateLimiter::clear('customer-checkout');
    RateLimiter::clear('payment-verify');
    RateLimiter::clear('payment-cancel');

    // 1. Warehouse
    $this->warehouse = Warehouse::create([
        'name' => 'Reliability Hub Delhi',
        'code' => 'DEL-REL-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    // 2. Primary Customer
    $this->customer = Customer::factory()->create([
        'name' => 'Aditi Sharma',
        'phone' => '+919811122233',
        'email' => 'aditi@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    // 3. Secondary Customer (for IDOR / Isolation tests)
    $this->otherCustomer = Customer::factory()->create([
        'name' => 'Vikram Malhotra',
        'phone' => '+919811122244',
        'email' => 'vikram@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    // 4. Eligible Delhi Address
    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919811122233',
        'address_line_1' => 'Mann Enclave, near Gurukul',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
        'is_default' => true,
    ]);

    // 5. Tax Setup (Standard 5%)
    $this->taxClass = TaxClass::create([
        'name' => 'Standard Botanical Goods',
        'slug' => 'standard-botanical-goods',
        'is_active' => true,
    ]);

    $this->taxRate = TaxRate::create([
        'name' => 'Delhi GST 5%',
        'rate' => 5.00,
        'is_active' => true,
    ]);

    TaxRule::create([
        'tax_class_id' => $this->taxClass->id,
        'tax_rate_id' => $this->taxRate->id,
        'country' => 'India',
        'state' => 'Delhi',
        'priority' => 1,
    ]);

    // 6. Product & Variant
    $this->product = Product::factory()->create([
        'name' => 'Areca Palm',
        'tax_class_id' => $this->taxClass->id,
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'ARECA-PLM-01',
        'price' => 600.00,
        'is_active' => true,
    ]);

    // 7. Inventory (Initial 20 units)
    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);
});

/*
|--------------------------------------------------------------------------
| A. Unpaid Order Expiry Tests
|--------------------------------------------------------------------------
*/

test('unpaid order older than configured threshold expires, cancels order, and restores inventory safely', function () {
    $order = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-EXP-001',
        'created_at' => now()->subMinutes(45),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => $this->product->name,
        'sku' => $this->variant->sku,
        'price' => 600.00,
        'quantity' => 2,
        'subtotal' => 1200.00,
        'tax_amount' => 60.00,
        'total' => 1260.00,
    ]);

    // Simulate inventory deduction at checkout
    app(InventoryService::class)->deductStockForOrder($this->variant, 2, $order);
    expect($this->inventory->fresh()->quantity)->toBe(18);

    // Create pending payment transaction
    $txn = PaymentTransaction::create([
        'order_id' => $order->id,
        'transaction_number' => 'TXN-EXP-001',
        'gateway' => 'null',
        'amount' => 1260.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
    ]);

    // 2. Run expiration command with threshold 30 minutes
    Artisan::call('orders:expire-unpaid', ['--minutes' => 30]);

    // 3. Verify order and payment status
    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CANCELLED);
    expect($order->payment_status)->toBe(PaymentStatus::EXPIRED);

    $txn->refresh();
    expect($txn->status)->toBe(PaymentStatus::EXPIRED);

    // 4. Verify inventory restored exactly to 20
    expect($this->inventory->fresh()->quantity)->toBe(20);

    // 5. Verify stock movement recorded as RELEASE
    $releaseMovement = StockMovement::where('reference_type', Order::class)
        ->where('reference_id', $order->id)
        ->where('type', StockMovementType::RELEASE)
        ->first();

    expect($releaseMovement)->not->toBeNull();
    expect($releaseMovement->quantity)->toBe(2);

    // 6. Verify order status history recorded
    $history = OrderStatusHistory::where('order_id', $order->id)
        ->where('to_status', OrderStatus::CANCELLED->value)
        ->first();

    expect($history)->not->toBeNull();
    expect($history->comment)->toContain('expired after 30 minutes');
});

test('unpaid order expiry command ignores already-cancelled or paid orders', function () {
    $paidOrder = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-EXP-PAID',
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
        'created_at' => now()->subMinutes(60),
    ]);

    $cancelledOrder = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-EXP-CANCELLED',
        'status' => OrderStatus::CANCELLED,
        'payment_status' => PaymentStatus::EXPIRED,
        'created_at' => now()->subMinutes(60),
    ]);

    Artisan::call('orders:expire-unpaid', ['--minutes' => 30]);

    expect($paidOrder->fresh()->status)->toBe(OrderStatus::PROCESSING);
    expect($cancelledOrder->fresh()->status)->toBe(OrderStatus::CANCELLED);
});

test('unpaid order expiry command is idempotent and does not double-release stock on repeated execution', function () {
    $order = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-EXP-IDEMP',
        'created_at' => now()->subMinutes(50),
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => $this->product->name,
        'sku' => $this->variant->sku,
        'price' => 600.00,
        'quantity' => 2,
        'subtotal' => 600.00,
        'tax_amount' => 0.00,
        'total' => 600.00,
    ]);

    app(InventoryService::class)->deductStockForOrder($this->variant, 2, $order);
    expect($this->inventory->fresh()->quantity)->toBe(18);

    // Run first time
    Artisan::call('orders:expire-unpaid', ['--minutes' => 30]);
    expect($this->inventory->fresh()->quantity)->toBe(20);

    // Run second time
    Artisan::call('orders:expire-unpaid', ['--minutes' => 30]);
    expect($this->inventory->fresh()->quantity)->toBe(20);

    // Only one release movement should exist
    $releaseMovements = StockMovement::where('reference_type', Order::class)
        ->where('reference_id', $order->id)
        ->where('type', StockMovementType::RELEASE)
        ->count();

    expect($releaseMovements)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| B. Checkout Idempotency Tests
|--------------------------------------------------------------------------
*/

test('same idempotency key creates one order and repeated request returns existing order safely', function () {
    loginReliabilityCustomer($this, $this->customer);

    $cart = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $idempotencyKey = (string) Str::uuid();

    $response1 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => $idempotencyKey,
    ]);

    $response1->assertRedirect();
    $ordersCount = Order::where('idempotency_key', $idempotencyKey)->count();
    expect($ordersCount)->toBe(1);

    $createdOrder = Order::where('idempotency_key', $idempotencyKey)->first();

    // Re-submit the exact same checkout with identical idempotency key
    $response2 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => $idempotencyKey,
    ]);

    $response2->assertRedirect(route('checkout.success', $createdOrder->order_number));

    // Still exactly 1 order in the database
    expect(Order::where('idempotency_key', $idempotencyKey)->count())->toBe(1);
    expect(Order::count())->toBe(1);
});

test('different idempotency keys create legitimate separate orders', function () {
    loginReliabilityCustomer($this, $this->customer);

    $cart = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $key1 = (string) Str::uuid();
    $response1 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => $key1,
    ]);
    $response1->assertRedirect();

    // Add item to cart again for order 2
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $key2 = (string) Str::uuid();
    $response2 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => $key2,
    ]);
    $response2->assertRedirect();

    expect(Order::count())->toBe(2);
    expect(Order::where('idempotency_key', $key1)->exists())->toBeTrue();
    expect(Order::where('idempotency_key', $key2)->exists())->toBeTrue();
});

test('invalid idempotency key format is rejected by validation', function () {
    loginReliabilityCustomer($this, $this->customer);

    $cart = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => 'invalid key with spaces & special chars!',
    ]);

    $response->assertSessionHasErrors(['idempotency_key']);
    expect(Order::count())->toBe(0);
});

test('idempotency key cannot cross customer scope', function () {
    $key = (string) Str::uuid();
    createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-SCOPE-001',
        'idempotency_key' => $key,
    ]);

    // Customer 2 attempts to use Customer 1's idempotency key
    loginReliabilityCustomer($this, $this->otherCustomer);

    $otherCart = Cart::create(['customer_id' => $this->otherCustomer->id]);
    CartItem::create([
        'cart_id' => $otherCart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $otherAddress = CustomerAddress::create([
        'customer_id' => $this->otherCustomer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Vikram Malhotra',
        'phone' => '+919811122244',
        'address_line_1' => 'Plot 9, Saket',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110017',
        'country' => 'India',
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $otherAddress->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => $key,
    ]);

    $response->assertSessionHasErrors(['idempotency_key']);
});

test('database uniqueness enforces idempotency key at schema level', function () {
    $key = 'db-unique-key-123';

    createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-DB-001',
        'idempotency_key' => $key,
    ]);

    expect(fn () => createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-DB-002',
        'idempotency_key' => $key,
    ]))->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| C. Checkout Concurrency & Inventory Protection Tests
|--------------------------------------------------------------------------
*/

test('checkout cannot oversell inventory when stock is depleted', function () {
    // Set variant inventory to exactly 1 unit
    $this->inventory->update(['quantity' => 1]);

    $cart1 = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart1->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $creationService = app(OrderCreationService::class);

    // First checkout succeeds
    $order1 = $creationService->createOrder($this->customer, $cart1, [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);

    expect($order1)->toBeInstanceOf(Order::class);
    expect($this->inventory->fresh()->quantity)->toBe(0);

    // Second checkout attempt fails due to insufficient stock
    $otherCart = Cart::create(['customer_id' => $this->otherCustomer->id]);
    CartItem::create([
        'cart_id' => $otherCart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $otherAddress = CustomerAddress::create([
        'customer_id' => $this->otherCustomer->id,
        'recipient_name' => 'Vikram',
        'phone' => '+919811122244',
        'address_line_1' => 'Saket',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110017',
    ]);

    expect(fn () => $creationService->createOrder($this->otherCustomer, $otherCart, [
        'shipping_address_id' => $otherAddress->id,
        'billing_same_as_shipping' => 1,
    ]))->toThrow(ValidationException::class);

    // Stock must remain 0 and never drop negative
    expect($this->inventory->fresh()->quantity)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| D. Payment Concurrency & Race Protection Tests
|--------------------------------------------------------------------------
*/

test('duplicate payment verification is idempotent and does not duplicate status history', function () {
    $order = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-PYMT-IDEMP',
    ]);

    $txn = PaymentTransaction::create([
        'order_id' => $order->id,
        'transaction_number' => 'TXN-PYMT-001',
        'gateway' => 'null',
        'amount' => 630.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
    ]);

    $paymentService = app(PaymentService::class);

    // 1st verification
    $res1 = $paymentService->processPaymentVerification($txn);
    expect($res1->success)->toBeTrue();
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);

    $historyCount = OrderStatusHistory::where('order_id', $order->id)->count();
    expect($historyCount)->toBe(1);

    // 2nd verification on same transaction
    $res2 = $paymentService->processPaymentVerification($txn);
    expect($res2->success)->toBeTrue();
    expect($res2->status)->toBe(PaymentStatus::PAID);

    // Status history is NOT duplicated
    expect(OrderStatusHistory::where('order_id', $order->id)->count())->toBe(1);
});

test('already PAID order cannot be downgraded by cancel payment', function () {
    $order = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-PYMT-DOWNGRADE',
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
    ]);

    $txn = PaymentTransaction::create([
        'order_id' => $order->id,
        'transaction_number' => 'TXN-DOWNGRADE-001',
        'gateway' => 'null',
        'amount' => 630.00,
        'status' => PaymentStatus::PENDING,
    ]);

    $paymentService = app(PaymentService::class);

    // Attempting to cancel payment on an already paid order must be rejected
    expect(fn () => $paymentService->cancelPayment($txn, 'Customer clicked cancel'))
        ->toThrow(DomainException::class);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

test('duplicate invoice generation is strictly prevented', function () {
    $order = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-INV-IDEMP',
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => $this->product->name,
        'sku' => $this->variant->sku,
        'price' => 600.00,
        'quantity' => 1,
        'subtotal' => 600.00,
        'tax_amount' => 30.00,
        'total' => 630.00,
    ]);

    $invoiceService = app(InvoiceService::class);

    $invoice1 = $invoiceService->getOrCreateInvoiceForOrder($order);
    $invoice2 = $invoiceService->getOrCreateInvoiceForOrder($order);

    expect($invoice1->id)->toBe($invoice2->id);
    expect($invoice1->invoice_number)->toBe($invoice2->invoice_number);
    expect(Invoice::where('order_id', $order->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| E. Rate Limiting Tests
|--------------------------------------------------------------------------
*/

test('checkout endpoint is throttled after 5 requests per minute', function () {
    loginReliabilityCustomer($this, $this->customer);

    $cart = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    // Perform 5 allowed requests
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->post(route('checkout.store'), [
            'shipping_address_id' => $this->address->id,
            'billing_same_as_shipping' => 1,
            'idempotency_key' => "rate-limit-key-{$i}",
        ]);

        expect($response->status())->not->toBe(429);

        // Replenish cart item for subsequent request
        if (! $cart->fresh()->items()->exists()) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
            ]);
        }
    }

    // 6th request must be throttled
    $response6 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => 'rate-limit-key-6',
    ]);

    $response6->assertStatus(429);
});

test('payment verify and cancel endpoints are throttled after 10 requests per minute', function () {
    loginReliabilityCustomer($this, $this->customer);

    $order = createReliabilityOrder($this->customer, [
        'order_number' => 'ORD-THROTTLE-PYMT',
    ]);

    $txn = PaymentTransaction::create([
        'order_id' => $order->id,
        'transaction_number' => 'TXN-THROTTLE-001',
        'gateway' => 'null',
        'amount' => 630.00,
        'status' => PaymentStatus::PENDING,
    ]);

    // 10 cancellation requests allowed
    for ($i = 1; $i <= 10; $i++) {
        $response = $this->post(route('checkout.payment.cancel'), [
            'transaction_number' => $txn->transaction_number,
        ]);

        expect($response->status())->not->toBe(429);
    }

    // 11th request throttled
    $response11 = $this->post(route('checkout.payment.cancel'), [
        'transaction_number' => $txn->transaction_number,
    ]);

    $response11->assertStatus(429);
});

/*
|--------------------------------------------------------------------------
| F. Config Cache & Runtime env() Safety Tests
|--------------------------------------------------------------------------
*/

test('config cache succeeds and configuration is loaded through config', function () {
    Artisan::call('config:clear');
    $exitCode = Artisan::call('config:cache');
    expect($exitCode)->toBe(0);

    // Confirm config values resolve
    expect(config('services.sms.driver'))->not->toBeNull();
    expect(config('ecommerce.shipping.flat_rate'))->toBe('0.00');

    // Clean up cache
    Artisan::call('config:clear');
});

test('no direct env calls exist in application runtime code', function () {
    $appPath = app_path();

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath)
    );

    $violations = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match('/\benv\s*\(/', $content)) {
                $violations[] = str_replace(base_path(), '', $file->getPathname());
            }
        }
    }

    expect($violations)->toBeEmpty();
});
