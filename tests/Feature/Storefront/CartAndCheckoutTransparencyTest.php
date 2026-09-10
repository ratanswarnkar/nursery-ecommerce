<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\Order\OrderCalculationService;
use App\Services\Payment\Gateways\RazorpayPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use Razorpay\Api\Api;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Delhi Greenhouse Hub',
        'code' => 'DEL-GH-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::factory()->create([
        'name' => 'Ananya Sharma',
        'phone' => '+919811223344',
        'email' => 'ananya@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    $this->ncrAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Ananya Sharma',
        'phone' => '+919811223344',
        'address_line_1' => 'Flat 204, Green Park Extension',
        'city' => 'South Delhi',
        'state' => 'Delhi',
        'postal_code' => '110016',
        'country' => 'India',
        'is_default' => true,
    ]);

    $this->product = Product::factory()->create([
        'name' => 'Fiddle Leaf Fig',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'FLF-MED',
        'price' => '450.00',
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);
});

test('cart delivery progress shows dynamic remaining amount when subtotal is below 1,000', function () {
    // subtotal = 450.00 * 2 = 900.00, remaining = 100.00
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('cart.index'));
    $response->assertOk();

    $response->assertSee('Add ₹100.00 more to get FREE delivery');
    $response->assertSee('Orders ABOVE ₹1,000 qualify for free delivery');
    $response->assertSee('Delhi NCR only');
    $response->assertSee('Delivery within 3 days');
    $response->assertSee('Calculated at checkout');
    $response->assertDontSee('You qualify for FREE delivery');
});

test('cart delivery progress explicitly communicates requirement when subtotal is exactly 1,000', function () {
    // 500.00 * 2 = 1,000.00
    $variant1000 = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'FLF-1000',
        'price' => '500.00',
        'is_active' => true,
    ]);
    Inventory::create([
        'product_variant_id' => $variant1000->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 20,
        'reserved_quantity' => 0,
        'safety_stock' => 1,
    ]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $variant1000->id,
        'quantity' => 2,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('cart.index'));
    $response->assertOk();

    // Exactly 1,000 must NOT show qualified state
    $response->assertDontSee('You qualify for FREE delivery');
    $response->assertSee('Add a little more to qualify for FREE delivery');
    $response->assertSee('Threshold: Above ₹1,000');
    $response->assertDontSee('₹1,000 or more');
    $response->assertDontSee('Free delivery on ₹1,000+');
    $response->assertSee('Calculated at checkout');
});

test('cart delivery progress shows celebration when subtotal is strictly above 1,000', function () {
    // 450.00 * 3 = 1,350.00
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 3,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('cart.index'));
    $response->assertOk();

    $response->assertSee('You qualify for FREE delivery');
    $response->assertSee('Delivery within 3 days');
    $response->assertSee('Delhi NCR only');
    $response->assertSee('Free Delivery Qualified');
    $response->assertSee('FREE');
});

test('empty cart does not show delivery progress banner', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('cart.index'));
    $response->assertOk();

    $response->assertSee('Your cart is empty');
    $response->assertDontSee('data-delivery-progress');
    $response->assertDontSee('more to get FREE delivery');
    $response->assertDontSee('You qualify for FREE delivery');
});

test('order calculation service calculates shipping deterministically across threshold boundaries', function () {
    $calc = app(OrderCalculationService::class);

    // Below 1,000: returns default flat rate without inventing arbitrary numbers
    expect($calc->calculateShipping('900.00'))->toBe('0.00');
    expect($calc->calculateShipping('999.99'))->toBe('0.00');

    // Exactly 1,000: NOT free, returns flat rate
    expect($calc->calculateShipping('1000.00'))->toBe('0.00');

    // Strictly above 1,000: FREE delivery
    expect($calc->calculateShipping('1000.01'))->toBe('0.00');
    expect($calc->calculateShipping('1001.00'))->toBe('0.00');
    expect($calc->calculateShipping('2500.00'))->toBe('0.00');
});

test('checkout displays FREE delivery when subtotal is strictly above 1,000', function () {
    // 450.00 * 3 = 1,350.00
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 3,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();

    $response->assertSee('FREE');
    $response->assertSee('You qualify for FREE delivery');
    $response->assertSee('Delhi NCR only');
    $response->assertSee('within 3 days');
});

test('checkout displays progress towards free delivery when subtotal is below 1,000', function () {
    // 450.00 * 1 = 450.00, remaining = 550.00
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();

    $response->assertSee('Add ₹550.00 more for FREE delivery');
    $response->assertSee('₹0.00'); // No invented shipping fee
    $response->assertSee('Delhi NCR only');
    $response->assertSee('within 3 days');
});

test('checkout displays explicit threshold message when subtotal is exactly 1,000', function () {
    $variant500 = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'FLF-CHK-1000',
        'price' => '500.00',
        'is_active' => true,
    ]);
    Inventory::create([
        'product_variant_id' => $variant500->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'safety_stock' => 1,
    ]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $variant500->id,
        'quantity' => 2, // 1,000.00
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();

    $response->assertSee('Add a little more to qualify for FREE delivery');
    $response->assertDontSee('You qualify for FREE delivery');
    $response->assertDontSee('₹1,000 or more');
    $response->assertDontSee('Free delivery on ₹1,000+');
});

test('checkout displays warning banner and guidance when customer has only non-Delhi-NCR addresses', function () {
    // Replace NCR address with non-NCR address
    $this->ncrAddress->delete();

    $nonNcrAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Ananya Sharma',
        'phone' => '+919811223344',
        'address_line_1' => 'MG Road, Indiranagar',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560038',
        'country' => 'India',
        'is_default' => true,
    ]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();
    $response->assertSee('No Eligible Delhi NCR Address Found');
    $response->assertSee('Outside Delhi NCR');
    $response->assertSee('This address is outside Delhi NCR and cannot be used for delivery.');

    // Attempting to checkout with outside NCR address must be rejected
    $submitResponse = $this->post(route('checkout.store'), [
        'shipping_address_id' => $nonNcrAddress->id,
        'billing_same_as_shipping' => 1,
    ]);

    $submitResponse->assertSessionHasErrors('shipping_address_id');
    expect(Order::count())->toBe(0);
});

test('checkout order placement succeeds with valid Delhi NCR address and persists server-calculated totals', function () {
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 3, // subtotal = 1,350.00
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->ncrAddress->id,
        'billing_same_as_shipping' => 1,
    ]);

    $order = Order::latest('id')->first();
    expect($order)->not->toBeNull()
        ->and((float) $order->subtotal)->toBe(1350.00)
        ->and((float) $order->shipping_amount)->toBe(0.00)
        ->and($order->status)->toBe(OrderStatus::PENDING);
});

test('server-side total cannot be manipulated and Razorpay amount matches server-side grand total in paise', function () {
    $testKeyId = 'rzp_test_mockKey123';
    $testKeySecret = 'testSecret45678910';

    config([
        'payment.default' => 'razorpay',
        'services.razorpay.key_id' => $testKeyId,
        'services.razorpay.key_secret' => $testKeySecret,
    ]);

    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();
    $orderResource->shouldReceive('create')
        ->once()
        ->withArgs(function ($payload) {
            return $payload['amount'] === 90000 // 900.00 * 100 paise
                && $payload['currency'] === 'INR';
        })
        ->andReturn((object) ['id' => 'order_rzp_mock_99999']);

    $mockApi->order = $orderResource;

    $gateway = new RazorpayPaymentGateway($mockApi, $testKeyId, $testKeySecret);
    app(PaymentGatewayManager::class)->extend('razorpay', fn () => $gateway);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2, // 900.00
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Client sends spoofed subtotal / grand_total fields
    $response = $this->postJson(route('checkout.store'), [
        'shipping_address_id' => $this->ncrAddress->id,
        'billing_same_as_shipping' => 1,
        'grand_total' => '1.00',
        'subtotal' => '1.00',
        'shipping_amount' => '0.00',
    ]);

    $response->assertOk();
    $data = $response->json();

    // 900.00 * 100 paise = 90000 paise (strictly calculated server-side)
    expect($data['amount'])->toBe(90000)
        ->and($data['currency'])->toBe('INR')
        ->and($data['razorpay_order_id'])->toBe('order_rzp_mock_99999');

    $order = Order::latest('id')->first();
    expect((float) $order->grand_total)->toBe(900.00);
});

test('cart item quantity update works properly and re-evaluates delivery progress dynamically', function () {
    $cart = Cart::create(['customer_id' => $this->customer->id, 'is_active' => true]);
    $item = CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1, // 450.00
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Below threshold (450.00)
    $res1 = $this->get(route('cart.index'));
    $res1->assertSee('Add ₹550.00 more to get FREE delivery');

    // Update quantity to 3 (1,350.00)
    $updateRes = $this->put(route('cart.items.update', $item), [
        'quantity' => 3,
    ]);
    $updateRes->assertRedirect();

    // Now above threshold
    $res2 = $this->get(route('cart.index'));
    $res2->assertSee('You qualify for FREE delivery');
    $res2->assertSee('FREE');
});
