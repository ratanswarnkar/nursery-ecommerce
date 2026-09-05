<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerOtpChallenge;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'name' => 'Fulfillment Hub',
        'code' => 'WH-FULFILL',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->product = Product::factory()->create(['is_active' => true]);
    $this->variant1 = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price' => '199.00',
        'is_active' => true,
    ]);
    $this->variant2 = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price' => '399.00',
        'is_active' => true,
    ]);

    Inventory::create([
        'product_variant_id' => $this->variant1->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'safety_stock' => 5,
    ]);

    Inventory::create([
        'product_variant_id' => $this->variant2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'safety_stock' => 5,
    ]);
});

test('customer cart is scoped to authenticated customer', function () {
    $customer = Customer::factory()->create(['is_active' => true]);

    $this->actingAs($customer, 'customer');
    session(['customer_auth_token_version' => $customer->auth_token_version]);

    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant1->id,
        'quantity' => 3,
    ]);

    $cart = Cart::where('customer_id', $customer->id)->where('is_active', true)->first();
    expect($cart)->not->toBeNull();
    expect($cart->items)->toHaveCount(1)
        ->and($cart->items->first()->quantity)->toBe(3);
});

test('guest cart merges into customer cart on OTP login', function () {
    // 1. Guest session adds items
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant1->id,
        'quantity' => 2,
    ]);

    $guestCart = Cart::whereNull('customer_id')->where('is_active', true)->first();
    expect($guestCart)->not->toBeNull()
        ->and($guestCart->items)->toHaveCount(1);

    // 2. Perform customer OTP login
    $phone = '+919876543210';
    $customer = Customer::create([
        'name' => 'Gardener Dave',
        'phone' => $phone,
        'is_active' => true,
        'auth_token_version' => 1,
    ]);

    // Seed OTP challenge
    CustomerOtpChallenge::create([
        'phone_e164' => $phone,
        'otp_hash' => Hash::make('123456'),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->post(route('customer.otp.verify'), [
        'phone' => $phone,
        'otp' => '123456',
    ]);
    $response->assertRedirect(route('customer.home'));

    // 3. Verify customer cart now holds the item
    $customerCart = Cart::where('customer_id', $customer->id)->where('is_active', true)->first();
    expect($customerCart)->not->toBeNull();

    $mergedItem = CartItem::where('cart_id', $customerCart->id)
        ->where('product_variant_id', $this->variant1->id)
        ->first();

    expect($mergedItem)->not->toBeNull()
        ->and($mergedItem->quantity)->toBe(2);

    // 4. Guest cart is deactivated
    $guestCart->refresh();
    expect($guestCart->is_active)->toBeFalse();
});

test('duplicate variants merge and quantities sum safely up to limit', function () {
    $phone = '+919876543211';
    $customer = Customer::create([
        'name' => 'Alice Green',
        'phone' => $phone,
        'is_active' => true,
        'auth_token_version' => 1,
    ]);

    // Customer already has 3 of variant1 in their cart
    $customerCart = Cart::create([
        'customer_id' => $customer->id,
        'is_active' => true,
    ]);
    CartItem::create([
        'cart_id' => $customerCart->id,
        'product_variant_id' => $this->variant1->id,
        'quantity' => 3,
    ]);

    // Guest adds 4 of variant1 and 1 of variant2
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant1->id,
        'quantity' => 4,
    ]);
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant2->id,
        'quantity' => 1,
    ]);

    // Log in
    CustomerOtpChallenge::create([
        'phone_e164' => $phone,
        'otp_hash' => Hash::make('654321'),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->post(route('customer.otp.verify'), [
        'phone' => $phone,
        'otp' => '654321',
    ]);

    // Customer cart should have:
    // variant1: 3 + 4 = 7
    // variant2: 1
    $item1 = CartItem::where('cart_id', $customerCart->id)->where('product_variant_id', $this->variant1->id)->first();
    $item2 = CartItem::where('cart_id', $customerCart->id)->where('product_variant_id', $this->variant2->id)->first();

    expect($item1)->not->toBeNull()
        ->and($item1->quantity)->toBe(7)
        ->and($item2)->not->toBeNull()
        ->and($item2->quantity)->toBe(1);
});

test('merge caps duplicate item quantity at maximum allowed limit of 50', function () {
    $phone = '+919876543212';
    $customer = Customer::create([
        'name' => 'Max Buyer',
        'phone' => $phone,
        'is_active' => true,
        'auth_token_version' => 1,
    ]);

    $customerCart = Cart::create([
        'customer_id' => $customer->id,
        'is_active' => true,
    ]);
    CartItem::create([
        'cart_id' => $customerCart->id,
        'product_variant_id' => $this->variant1->id,
        'quantity' => 35,
    ]);

    // Guest has 30 (35 + 30 = 65 > 50)
    $this->post(route('cart.items.store'), [
        'product_variant_id' => $this->variant1->id,
        'quantity' => 30,
    ]);

    CustomerOtpChallenge::create([
        'phone_e164' => $phone,
        'otp_hash' => Hash::make('111222'),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'attempts' => 0,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->post(route('customer.otp.verify'), [
        'phone' => $phone,
        'otp' => '111222',
    ]);

    $item = CartItem::where('cart_id', $customerCart->id)->where('product_variant_id', $this->variant1->id)->first();
    expect($item->quantity)->toBe(50); // Capped at 50
});

test('IDOR: customer B cannot update or delete customer A cart item', function () {
    $customerA = Customer::factory()->create(['is_active' => true]);
    $customerB = Customer::factory()->create(['is_active' => true]);

    $cartA = Cart::create(['customer_id' => $customerA->id, 'is_active' => true]);
    $itemA = CartItem::create([
        'cart_id' => $cartA->id,
        'product_variant_id' => $this->variant1->id,
        'quantity' => 2,
    ]);

    // Customer B acts
    $this->actingAs($customerB, 'customer');
    session(['customer_auth_token_version' => $customerB->auth_token_version]);

    // Attempting to update customer A's item -> 404 (IDOR blocked)
    $updateResponse = $this->put(route('cart.items.update', $itemA), [
        'quantity' => 10,
    ]);
    $updateResponse->assertNotFound();

    // Item quantity remains unchanged
    expect($itemA->fresh()->quantity)->toBe(2);

    // Attempting to delete customer A's item -> 404 (IDOR blocked)
    $deleteResponse = $this->delete(route('cart.items.destroy', $itemA));
    $deleteResponse->assertNotFound();

    expect(CartItem::find($itemA->id))->not->toBeNull();
});
