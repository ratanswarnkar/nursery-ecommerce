<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\TaxRule;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // 1. Warehouse
    $this->warehouse = Warehouse::create([
        'name' => 'Delhi Greenhouse Hub',
        'code' => 'DEL-GH-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    // 2. Customer
    $this->customer = Customer::factory()->create([
        'name' => 'Kavita Roy',
        'phone' => '+919876543210',
        'email' => 'kavita@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    // 3. Customer Address
    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Kavita Roy',
        'phone' => '+919876543210',
        'address_line_1' => 'Plot 42, Vasant Vihar',
        'city' => 'New Delhi',
        'state' => 'Delhi',
        'postal_code' => '110057',
        'country' => 'India',
        'is_default' => true,
    ]);

    // 4. Products & Variants
    $this->product = Product::factory()->create([
        'name' => 'Monstera Deliciosa',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'MON-DEL-MD',
        'price' => '499.00',
        'is_active' => true,
    ]);

    // 5. Inventory
    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);
});

test('unauthenticated guest accessing /checkout is redirected to customer login', function () {
    $response = $this->get(route('checkout.index'));
    $response->assertRedirect(route('customer.login'));
});

test('authenticated customer with empty cart is redirected to cart with notice', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('checkout.index'));
    $response->assertRedirect(route('cart.index'));
    $response->assertSessionHas('info');
});

test('authenticated customer with valid cart can access checkout page and see addresses and order summary', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Create active cart with item
    $cart = Cart::create([
        'customer_id' => $this->customer->id,
        'is_active' => true,
        'last_activity_at' => now(),
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();
    $response->assertSee('Select Delivery Address');
    $response->assertSee('Plot 42, Vasant Vihar');
    $response->assertSee('Monstera Deliciosa');
    $response->assertSee('₹998.00'); // 2 * 499.00
});

test('address IDOR protection: customer cannot submit an address belonging to another customer', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $otherCustomer = Customer::factory()->create(['auth_token_version' => 1]);
    $otherAddress = CustomerAddress::create([
        'customer_id' => $otherCustomer->id,
        'address_type' => AddressType::WORK,
        'recipient_name' => 'Stranger',
        'phone' => '+919999999999',
        'address_line_1' => 'Unknown Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400001',
        'country' => 'India',
        'is_default' => true,
    ]);

    $cart = Cart::create([
        'customer_id' => $this->customer->id,
        'is_active' => true,
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $otherAddress->id,
        'billing_same_as_shipping' => 1,
    ]);

    $response->assertSessionHasErrors(['shipping_address_id']);
    expect(Order::count())->toBe(0);
});

test('checkout server-side price tampering protection: prices are strictly resolved from database', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create([
        'customer_id' => $this->customer->id,
        'is_active' => true,
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    // Attacker attempts to send manipulated subtotal or line price
    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'price' => '1.00',
        'subtotal' => '2.00',
        'grand_total' => '2.00',
    ]);

    $order = Order::latest('id')->first();
    expect($order)->not->toBeNull();
    // Subtotal must be calculated server-side: 2 * 499.00 = 998.00
    expect((float) $order->subtotal)->toBe(998.00)
        ->and((float) $order->grand_total)->toBe(998.00);

    $orderItem = $order->items->first();
    expect((float) $orderItem->price)->toBe(499.00)
        ->and((float) $orderItem->subtotal)->toBe(998.00);
});

test('checkout stock validation: fails gracefully when variant has insufficient stock', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Reduce warehouse inventory to 1
    $this->inventory->update(['quantity' => 1]);

    $cart = Cart::create([
        'customer_id' => $this->customer->id,
        'is_active' => true,
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 3, // Requesting 3 when only 1 exists
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);

    $response->assertSessionHasErrors(['stock']);
    expect(Order::count())->toBe(0);
    // Inventory must remain untouched
    expect($this->inventory->fresh()->quantity)->toBe(1);
    // Cart must NOT be cleared
    expect($cart->fresh()->items)->toHaveCount(1);
});

test('successful checkout creates order, snapshots customer and addresses, records items, and clears cart', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create([
        'customer_id' => $this->customer->id,
        'is_active' => true,
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'notes' => 'Please ring the doorbell upon arrival.',
    ]);

    $order = Order::latest('id')->first();
    expect($order)->not->toBeNull();

    $response->assertRedirect(route('checkout.success', $order->order_number));

    // Historical snapshots verification
    expect($order->customer_id)->toBe($this->customer->id)
        ->and($order->customer_name)->toBe('Kavita Roy')
        ->and($order->customer_phone)->toBe('+919876543210')
        ->and($order->customer_email)->toBe('kavita@example.com')
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->payment_status)->toBe(PaymentStatus::PENDING)
        ->and($order->currency)->toBe('INR')
        ->and($order->notes)->toBe('Please ring the doorbell upon arrival.')
        ->and($order->shipping_address_json['recipient_name'])->toBe('Kavita Roy')
        ->and($order->shipping_address_json['address_line_1'])->toBe('Plot 42, Vasant Vihar')
        ->and($order->shipping_address_json['postal_code'])->toBe('110057')
        ->and($order->billing_address_json['recipient_name'])->toBe('Kavita Roy');

    // Order items
    expect($order->items)->toHaveCount(1);
    $item = $order->items->first();
    expect($item->product_name)->toBe('Monstera Deliciosa')
        ->and($item->sku)->toBe('MON-DEL-MD')
        ->and((float) $item->price)->toBe(499.00)
        ->and($item->quantity)->toBe(2)
        ->and((float) $item->subtotal)->toBe(998.00);

    // Order status history created
    expect($order->statusHistories)->toHaveCount(1)
        ->and($order->statusHistories->first()->to_status)->toBe(OrderStatus::PENDING);

    // Cart cleared
    expect($cart->fresh()->items)->toHaveCount(0);
});

test('stock decrement: placing order decrements physical inventory and logs outbound stock movements', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create([
        'customer_id' => $this->customer->id,
        'is_active' => true,
    ]);

    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 4,
    ]);

    $initialStock = $this->inventory->quantity; // 20

    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);

    // Physical stock decremented from 20 to 16
    expect($this->inventory->fresh()->quantity)->toBe($initialStock - 4);

    // Outbound stock movement logged
    $movement = StockMovement::where('product_variant_id', $this->variant->id)->latest('id')->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe(StockMovementType::OUTBOUND)
        ->and($movement->quantity)->toBe(-4)
        ->and($movement->previous_quantity)->toBe(20)
        ->and($movement->new_quantity)->toBe(16);
});

test('order number uniqueness: each placed order receives a distinct non-sequential order number', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);

    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $order1 = Order::latest('id')->first();

    // Place second order
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);
    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $order2 = Order::latest('id')->first();

    expect($order1->order_number)->toStartWith('ORD-')
        ->and($order2->order_number)->toStartWith('ORD-')
        ->and($order1->order_number)->not->toBe($order2->order_number);
});

test('tax calculation: order accurately computes item-level taxes based on TaxClass and TaxRule', function () {
    $taxClass = TaxClass::create(['name' => 'Plants GST 18%', 'is_active' => true]);
    $taxRate = TaxRate::create(['name' => 'GST 18%', 'rate' => 18.00, 'is_active' => true]);
    TaxRule::create([
        'tax_class_id' => $taxClass->id,
        'tax_rate_id' => $taxRate->id,
        'country' => 'IN',
        'is_active' => true,
        'priority' => 1,
    ]);

    $this->product->update(['tax_class_id' => $taxClass->id]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]); // 499.00

    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);

    $order = Order::latest('id')->first();
    // 499.00 * 0.18 = 89.82
    expect((float) $order->subtotal)->toBe(499.00)
        ->and((float) $order->tax_amount)->toBe(89.82)
        ->and((float) $order->grand_total)->toBe(588.82); // 499 + 89.82
});

test('order confirmation access: customer can access their own confirmation page; unauthorized customer gets 404', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);

    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $order = Order::latest('id')->first();

    // Owner can access
    $response = $this->get(route('checkout.success', $order->order_number));
    $response->assertOk();
    $response->assertSee($order->order_number);
    $response->assertSee('Thank You, Kavita Roy!');

    // Another customer attempting to access gets 404
    $anotherCustomer = Customer::factory()->create(['auth_token_version' => 1]);
    $this->actingAs($anotherCustomer, 'customer');
    session(['customer_auth_token_version' => $anotherCustomer->auth_token_version]);

    $idorResponse = $this->get(route('checkout.success', $order->order_number));
    $idorResponse->assertNotFound();
});

test('customer order portal: customer can view order history and individual order details', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);

    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $order = Order::latest('id')->first();

    // Order list
    $listResponse = $this->get(route('account.orders.index'));
    $listResponse->assertOk();
    $listResponse->assertSee($order->order_number);

    // Order show
    $showResponse = $this->get(route('account.orders.show', $order->order_number));
    $showResponse->assertOk();
    $showResponse->assertSee($order->order_number);
    $showResponse->assertSee('Monstera Deliciosa');
    $showResponse->assertSee('Plot 42, Vasant Vihar');

    // IDOR protection on show
    $otherCustomer = Customer::factory()->create(['auth_token_version' => 1]);
    $this->actingAs($otherCustomer, 'customer');
    session(['customer_auth_token_version' => $otherCustomer->auth_token_version]);

    $unauthorizedResponse = $this->get(route('account.orders.show', $order->order_number));
    $unauthorizedResponse->assertNotFound();
});

test('admin order access: admin with orders.view can view orders list and detail; unauthorized admin is blocked', function () {
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);
    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $order = Order::latest('id')->first();

    // Ensure permission exists
    Permission::firstOrCreate(['name' => 'orders.view', 'guard_name' => 'admin']);

    // Admin with orders.view permission
    $authorizedAdmin = Admin::factory()->create(['is_active' => true, 'auth_token_version' => 1]);
    $authorizedAdmin->givePermissionTo('orders.view');

    $this->actingAs($authorizedAdmin, 'admin');
    session(['admin_auth_token_version' => $authorizedAdmin->auth_token_version]);

    $adminList = $this->get(route('admin.orders.index'));
    $adminList->assertOk();
    $adminList->assertSee($order->order_number);

    $adminDetail = $this->get(route('admin.orders.show', $order));
    $adminDetail->assertOk();
    $adminDetail->assertSee($order->order_number);
    $adminDetail->assertSee('Kavita Roy');

    // Admin without orders.view permission is blocked
    $unauthorizedAdmin = Admin::factory()->create(['is_active' => true, 'auth_token_version' => 1]);
    $this->actingAs($unauthorizedAdmin, 'admin');
    session(['admin_auth_token_version' => $unauthorizedAdmin->auth_token_version]);

    $blockedResponse = $this->get(route('admin.orders.index'));
    $blockedResponse->assertForbidden();
});

test('shipping calculation boundary defaults to 0.00 without hardcoding business policies', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);

    $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);

    $order = Order::latest('id')->first();
    expect((float) $order->shipping_amount)->toBe(0.00)
        ->and((float) $order->discount_amount)->toBe(0.00);
});

test('transaction rollback: exception during stock deduction rolls back order creation completely and preserves cart', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 10]);

    // Mock or simulate an exception during stock deduction by setting inventory quantity below requested 10
    $this->inventory->update(['quantity' => 5]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);

    $response->assertSessionHasErrors(['stock']);
    expect(Order::count())->toBe(0)
        ->and($cart->fresh()->items)->toHaveCount(1)
        ->and($this->inventory->fresh()->quantity)->toBe(5);
});

test('duplicate submission protection: subsequent checkout attempt with already cleared cart is rejected safely', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $this->variant->id, 'quantity' => 1]);

    // First checkout succeeds
    $response1 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $order1 = Order::latest('id')->first();
    $response1->assertRedirect(route('checkout.success', $order1->order_number));

    // Immediate replay/duplicate submission on now-empty cart
    $response2 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
    ]);
    $response2->assertSessionHasErrors(['cart']);

    // Only 1 order was created
    expect(Order::count())->toBe(1);
});
