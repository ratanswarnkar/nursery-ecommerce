<?php

use App\Enums\AddressType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\TaxRule;
use App\Models\Warehouse;
use App\Services\Order\OrderCreationService;
use App\Services\Shipping\DelhiNcrEligibilityService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->eligibilityService = app(DelhiNcrEligibilityService::class);

    $this->warehouse = Warehouse::create([
        'name' => 'Delhi Greenhouse Hub',
        'code' => 'DEL-GH-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::factory()->create([
        'name' => 'Aditi Sharma',
        'phone' => '+919876543210',
        'email' => 'aditi@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);
});

/*
|--------------------------------------------------------------------------
| A. Delhi NCR Delivery Validation
|--------------------------------------------------------------------------
*/

test('valid NCT Delhi address passes eligibility service', function () {
    $address = [
        'country' => 'India',
        'state' => 'Delhi',
        'city' => 'New Delhi',
        'postal_code' => '110001',
    ];

    expect($this->eligibilityService->isEligible($address))->toBeTrue();

    $nurseryHubAddress = [
        'country' => 'India',
        'state' => 'Delhi',
        'city' => 'Delhi',
        'postal_code' => '110082',
    ];

    expect($this->eligibilityService->isEligible($nurseryHubAddress))->toBeTrue();
});

test('allowlisted Haryana and Uttar Pradesh NCR core cities pass eligibility service', function () {
    // Gurugram (122)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Haryana',
        'city' => 'Gurugram',
        'postal_code' => '122001',
    ]))->toBeTrue();

    // Faridabad (121)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Haryana',
        'city' => 'Faridabad',
        'postal_code' => '121001',
    ]))->toBeTrue();

    // Noida (2013)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Uttar Pradesh',
        'city' => 'Noida',
        'postal_code' => '201301',
    ]))->toBeTrue();

    // Greater Noida (2013)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Uttar Pradesh',
        'city' => 'Greater Noida',
        'postal_code' => '201310',
    ]))->toBeTrue();

    // Ghaziabad (2010)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Uttar Pradesh',
        'city' => 'Ghaziabad',
        'postal_code' => '201001',
    ]))->toBeTrue();
});

test('clearly unsupported states and addresses fail eligibility service', function () {
    // Maharashtra / Mumbai
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Maharashtra',
        'city' => 'Mumbai',
        'postal_code' => '400001',
    ]))->toBeFalse();

    // Karnataka / Bengaluru
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Karnataka',
        'city' => 'Bengaluru',
        'postal_code' => '560001',
    ]))->toBeFalse();

    // Spoofed city: state is Maharashtra but city says Delhi
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Maharashtra',
        'city' => 'Delhi',
        'postal_code' => '400001',
    ]))->toBeFalse();

    // State says Delhi but postal code is Mumbai (400001)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Delhi',
        'city' => 'Delhi',
        'postal_code' => '400001',
    ]))->toBeFalse();

    // Non-NCR city in Haryana (Hisar)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Haryana',
        'city' => 'Hisar',
        'postal_code' => '125001',
    ]))->toBeFalse();

    // Non-NCR city in Uttar Pradesh (Lucknow)
    expect($this->eligibilityService->isEligible([
        'country' => 'India',
        'state' => 'Uttar Pradesh',
        'city' => 'Lucknow',
        'postal_code' => '226001',
    ]))->toBeFalse();

    // Non-India country
    expect($this->eligibilityService->isEligible([
        'country' => 'United States',
        'state' => 'California',
        'city' => 'Los Angeles',
        'postal_code' => '90001',
    ]))->toBeFalse();
});

test('CustomerAddress model exposes isDelhiNcr helper accurately', function () {
    $delhiAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919876543210',
        'address_line_1' => 'Plot 10, Rohini',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110085',
        'country' => 'India',
        'is_default' => true,
    ]);

    $puneAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::WORK,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919876543210',
        'address_line_1' => 'Tower A, Hinjewadi',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'postal_code' => '411057',
        'country' => 'India',
        'is_default' => false,
    ]);

    expect($delhiAddress->isDelhiNcr())->toBeTrue();
    expect($puneAddress->isDelhiNcr())->toBeFalse();
});

test('checkout cannot place an order with an unsupported out-of-NCR delivery address', function () {
    $taxClass = TaxClass::create(['name' => 'Standard GST', 'is_active' => true]);
    $taxRate = TaxRate::create(['name' => 'GST 18%', 'rate' => 18.00, 'is_active' => true]);
    TaxRule::create(['tax_class_id' => $taxClass->id, 'tax_rate_id' => $taxRate->id, 'country' => 'IN', 'priority' => 1, 'is_active' => true]);

    $product = Product::factory()->create(['is_active' => true, 'tax_class_id' => $taxClass->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => '800.00', 'is_active' => true]);
    Inventory::create(['product_variant_id' => $variant->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 20]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'session_id' => session()->getId(), 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

    $unsupportedAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919876543210',
        'address_line_1' => 'MG Road',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'country' => 'India',
        'is_default' => true,
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $unsupportedAddress->id,
        'billing_same_as_shipping' => 1,
    ]);

    $response->assertSessionHasErrors(['shipping_address_id']);
    $errors = session('errors')->get('shipping_address_id');
    expect($errors[0])->toContain('Delivery is currently available only within Delhi NCR');

    // Confirm order was not created
    expect(Order::count())->toBe(0);
});

test('transactional OrderCreationService strictly rejects unsupported delivery address', function () {
    $taxClass = TaxClass::create(['name' => 'Standard GST', 'is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'tax_class_id' => $taxClass->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => '500.00', 'is_active' => true]);
    Inventory::create(['product_variant_id' => $variant->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'session_id' => 'test-session', 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

    $mumbaiAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919876543210',
        'address_line_1' => 'Nariman Point',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400021',
        'country' => 'India',
        'is_default' => true,
    ]);

    $orderCreationService = app(OrderCreationService::class);

    expect(fn () => $orderCreationService->createOrder($this->customer, $cart, [
        'shipping_address_id' => $mumbaiAddress->id,
        'billing_same_as_shipping' => 1,
    ]))->toThrow(ValidationException::class);
});

test('checkout succeeds with valid supported Delhi NCR address', function () {
    $taxClass = TaxClass::create(['name' => 'Standard GST', 'is_active' => true]);
    $taxRate = TaxRate::create(['name' => 'GST 18%', 'rate' => 18.00, 'is_active' => true]);
    TaxRule::create(['tax_class_id' => $taxClass->id, 'tax_rate_id' => $taxRate->id, 'country' => 'IN', 'priority' => 1, 'is_active' => true]);

    $product = Product::factory()->create(['is_active' => true, 'tax_class_id' => $taxClass->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => '600.00', 'is_active' => true]);
    Inventory::create(['product_variant_id' => $variant->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 20]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'session_id' => session()->getId(), 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

    $delhiAddress = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919876543210',
        'address_line_1' => 'Plot 42, Vasant Vihar',
        'city' => 'New Delhi',
        'state' => 'Delhi',
        'postal_code' => '110057',
        'country' => 'India',
        'is_default' => true,
    ]);

    $response = $this->post(route('checkout.store'), [
        'shipping_address_id' => $delhiAddress->id,
        'billing_same_as_shipping' => 1,
    ]);

    $response->assertSessionHasNoErrors();
    expect(Order::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| B. Threshold Wording & Business Rule
|--------------------------------------------------------------------------
*/

test('checkout view communicates Delhi NCR, 3 days, and ABOVE ₹1,000 threshold accurately', function () {
    $taxClass = TaxClass::create(['name' => 'Standard GST', 'is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'tax_class_id' => $taxClass->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => '500.00', 'is_active' => true]);
    Inventory::create(['product_variant_id' => $variant->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'session_id' => session()->getId(), 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();

    // Verify required delivery highlights
    $response->assertSee('Delivery available only within Delhi NCR.');
    $response->assertSee('Delivery is expected within 3 days.');
    $response->assertSee('Orders ABOVE ₹1,000 qualify for FREE delivery.');

    // Verify strict wording: exactly ₹1,000 is NOT free; does not state "₹1,000 or more"
    $response->assertDontSee('₹1,000 or more qualify for free delivery');
    $response->assertDontSee('orders of ₹1,000 qualify for free delivery');
});

/*
|--------------------------------------------------------------------------
| C. Navigation ("My Orders")
|--------------------------------------------------------------------------
*/

test('authenticated customer sees My Orders in desktop header dropdown and mobile navigation', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $view = $this->blade('<x-storefront.header />');
    $view->assertSee(route('account.orders.index'));
    $view->assertSee('My Orders');
});

test('guest does not see My Orders in header dropdown', function () {
    $view = $this->blade('<x-storefront.header />');
    $view->assertDontSee(route('account.orders.index'));
    $view->assertSee(route('customer.login'));
});

test('customer dashboard contains dedicated My Orders shortcut card', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.dashboard'));
    $response->assertOk();

    $response->assertSee(route('account.orders.index'));
    $response->assertSee('My Orders');
    $response->assertSee('Track greenhouse shipments, view invoices, and request returns.');
});

/*
|--------------------------------------------------------------------------
| D. Footer Popular Categories
|--------------------------------------------------------------------------
*/

test('footer popular categories resolve to real category routes without broken fabricated routes', function () {
    $indoor = Category::create([
        'name' => 'Indoor Plants',
        'slug' => 'indoor-plants',
        'is_active' => true,
    ]);

    $airPurifying = Category::create([
        'name' => 'Air Purifying Plants',
        'slug' => 'air-purifying',
        'parent_id' => $indoor->id,
        'is_active' => true,
    ]);

    $flowering = Category::create([
        'name' => 'Flowering Plants',
        'slug' => 'flowering-plants',
        'is_active' => true,
    ]);

    $terracotta = Category::create([
        'name' => 'Terracotta Pots',
        'slug' => 'terracotta-pots',
        'is_active' => true,
    ]);

    $plantCare = Category::create([
        'name' => 'Plant Care & Soils',
        'slug' => 'plant-care',
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));
    $response->assertOk();

    // Verify all 5 links in footer resolve to valid URLs
    $response->assertSee(route('categories.show', 'air-purifying'));
    $response->assertSee(route('categories.show', 'flowering-plants'));
    $response->assertSee(route('categories.show', 'terracotta-pots'));
    $response->assertSee(route('categories.show', 'plant-care'));
    $response->assertSee(route('categories.show', 'indoor-plants'));

    // Confirm that visiting each real category returns HTTP 200 (not 404)
    $this->get(route('categories.show', 'air-purifying'))->assertOk();
    $this->get(route('categories.show', 'flowering-plants'))->assertOk();
    $this->get(route('categories.show', 'terracotta-pots'))->assertOk();
    $this->get(route('categories.show', 'plant-care'))->assertOk();
    $this->get(route('categories.show', 'indoor-plants'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| E. Placeholder Cleanup
|--------------------------------------------------------------------------
*/

test('outdated Phase 6 development placeholder text is no longer present in affected views', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Check Address Book view
    $addrResponse = $this->get(route('account.addresses.index'));
    $addrResponse->assertOk();
    $addrResponse->assertDontSee('when Phase 6 launches');
    $addrResponse->assertSee('Delivery available only within Delhi NCR.');

    // Check Checkout view (with cart item)
    $taxClass = TaxClass::create(['name' => 'Standard GST', 'is_active' => true]);
    $product = Product::factory()->create(['is_active' => true, 'tax_class_id' => $taxClass->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => '500.00', 'is_active' => true]);
    Inventory::create(['product_variant_id' => $variant->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);

    $cart = Cart::create(['customer_id' => $this->customer->id, 'session_id' => session()->getId(), 'is_active' => true]);
    CartItem::create(['cart_id' => $cart->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);

    $checkoutResponse = $this->get(route('checkout.index'));
    $checkoutResponse->assertOk();
    $checkoutResponse->assertDontSee('Order Foundation (Phase 6.1)');
    $checkoutResponse->assertDontSee('Payment gateway integration will be finalized in Phase 6.2.');
});
