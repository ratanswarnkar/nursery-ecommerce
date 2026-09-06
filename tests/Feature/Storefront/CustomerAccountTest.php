<?php

use App\Enums\AddressType;
use App\Models\Customer;
use App\Models\CustomerAddress;

beforeEach(function () {
    $this->customer = Customer::factory()->create([
        'name' => 'Aditi Sharma',
        'email' => 'aditi@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);
});

test('unauthenticated visitor to /account is redirected to customer login', function () {
    $response = $this->get(route('account.dashboard'));
    $response->assertRedirect(route('customer.login'));
});

test('authenticated customer can access dashboard with profile data and order placeholder', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.dashboard'));
    $response->assertOk();
    $response->assertSee('Aditi Sharma');
    $response->assertSee('No orders placed yet');
});

test('customer can update their profile information and email uniqueness is validated', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Successful update
    $response = $this->put(route('account.profile.update'), [
        'name' => 'Aditi S. Sharma',
        'email' => 'aditi.new@example.com',
    ]);
    $response->assertRedirect(route('account.profile'));
    $this->assertDatabaseHas('customers', [
        'id' => $this->customer->id,
        'name' => 'Aditi S. Sharma',
        'email' => 'aditi.new@example.com',
    ]);

    // Duplicate email from another customer fails validation
    $otherCustomer = Customer::factory()->create(['email' => 'other@example.com', 'auth_token_version' => 1]);

    $dupResponse = $this->put(route('account.profile.update'), [
        'name' => 'Aditi Sharma',
        'email' => 'other@example.com',
    ]);
    $dupResponse->assertSessionHasErrors(['email']);
});

test('creating the first address automatically sets it as the default address', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->post(route('account.addresses.store'), [
        'address_type' => AddressType::HOME->value,
        'recipient_name' => 'Aditi Home',
        'phone' => '+919876543210',
        'address_line_1' => 'Flat 402, Green Acre Apartments',
        'city' => 'Bengaluru',
        'state' => 'Karnataka',
        'postal_code' => '560001',
        'is_default' => false, // even if false, first address becomes default
    ]);

    $response->assertRedirect(route('account.addresses.index'));

    $address = $this->customer->addresses()->first();
    expect($address)->not->toBeNull();
    expect($address->is_default)->toBeTrue();
});

test('setting a new default unsets previous default ensuring single-default invariant', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $addr1 = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Home',
        'phone' => '+919876543210',
        'address_line_1' => 'House 1',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'postal_code' => '411001',
        'is_default' => true,
    ]);

    $addr2 = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::WORK,
        'recipient_name' => 'Office',
        'phone' => '+919876543210',
        'address_line_1' => 'Tower B',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'postal_code' => '411002',
        'is_default' => false,
    ]);

    $this->post(route('account.addresses.set-default', $addr2));

    expect($addr1->fresh()->is_default)->toBeFalse();
    expect($addr2->fresh()->is_default)->toBeTrue();

    // Verify invariant: exactly one default
    $defaultCount = $this->customer->addresses()->where('is_default', true)->count();
    expect($defaultCount)->toBe(1);
});

test('deleting the default address automatically promotes the oldest remaining address to default', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $oldestAddr = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Oldest Residence',
        'phone' => '+919876543210',
        'address_line_1' => 'Old House',
        'city' => 'Jaipur',
        'state' => 'Rajasthan',
        'postal_code' => '302001',
        'is_default' => false,
        'created_at' => now()->subDays(5),
    ]);

    $defaultAddr = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::WORK,
        'recipient_name' => 'Current Default Office',
        'phone' => '+919876543210',
        'address_line_1' => 'New Office',
        'city' => 'Jaipur',
        'state' => 'Rajasthan',
        'postal_code' => '302002',
        'is_default' => true,
        'created_at' => now(),
    ]);

    // Delete default address
    $response = $this->delete(route('account.addresses.destroy', $defaultAddr));
    $response->assertRedirect(route('account.addresses.index'));

    $this->assertDatabaseMissing('customer_addresses', ['id' => $defaultAddr->id]);

    // Oldest remaining must now be default
    expect($oldestAddr->fresh()->is_default)->toBeTrue();
});

test('strict IDOR protection: customer cannot view, update, or delete another customer address', function () {
    $otherCustomer = Customer::factory()->create(['is_active' => true, 'auth_token_version' => 1]);
    $otherAddress = CustomerAddress::create([
        'customer_id' => $otherCustomer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Other Person Home',
        'phone' => '+919111111111',
        'address_line_1' => 'Secret Villa',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400001',
        'is_default' => true,
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Attempting to update other customer's address must return 404
    $updateResponse = $this->put(route('account.addresses.update', $otherAddress), [
        'address_type' => AddressType::HOME->value,
        'recipient_name' => 'Hacked Name',
        'phone' => '+919876543210',
        'address_line_1' => 'Hacked Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400001',
    ]);
    $updateResponse->assertNotFound();

    // Attempting to delete other customer's address must return 404
    $deleteResponse = $this->delete(route('account.addresses.destroy', $otherAddress));
    $deleteResponse->assertNotFound();

    // Verify other address remains untouched
    expect($otherAddress->fresh()->recipient_name)->toBe('Other Person Home');
});
