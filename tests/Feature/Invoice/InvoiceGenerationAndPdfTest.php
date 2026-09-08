<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Exceptions\Invoice\IneligibleForInvoiceException;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\Invoice\InvoiceNumberGenerator;
use App\Services\Invoice\InvoiceService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
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
        'name' => 'Kavita Rao',
        'phone' => '+919876543210',
        'email' => 'kavita@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    // 3. Address
    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Kavita Rao',
        'phone' => '+919876543210',
        'address_line_1' => 'Flat 402, Green Meadows',
        'address_line_2' => 'Near Central Park',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
        'is_default' => true,
    ]);

    // 4. Product & Variant
    $this->product = Product::factory()->create([
        'name' => 'Monstera Deliciosa',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'MON-DEL-01',
        'price' => 850.00,
        'is_active' => true,
    ]);

    // 5. Paid Order with OrderItem
    $this->paidOrder = Order::create([
        'order_number' => 'ORD-20260907-INV001',
        'customer_id' => $this->customer->id,
        'customer_name' => 'Kavita Rao',
        'customer_phone' => '+919876543210',
        'customer_email' => 'kavita@example.com',
        'status' => OrderStatus::PROCESSING,
        'payment_status' => PaymentStatus::PAID,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 1700.00,
        'tax_amount' => 85.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 50.00,
        'grand_total' => 1735.00,
        'shipping_address_json' => [
            'full_name' => 'Kavita Rao',
            'phone' => '+919876543210',
            'address_line1' => 'Flat 402, Green Meadows',
            'address_line2' => 'Near Central Park',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
        'billing_address_json' => [
            'full_name' => 'Kavita Rao',
            'phone' => '+919876543210',
            'address_line1' => 'Flat 402, Green Meadows',
            'address_line2' => 'Near Central Park',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110082',
            'country' => 'India',
        ],
    ]);

    $this->orderItem = OrderItem::create([
        'order_id' => $this->paidOrder->id,
        'product_variant_id' => $this->variant->id,
        'product_name' => 'Monstera Deliciosa',
        'variant_name' => 'Medium Ceramic Pot',
        'sku' => 'MON-DEL-01',
        'price' => 850.00,
        'quantity' => 2,
        'subtotal' => 1700.00,
        'tax_amount' => 85.00,
        'discount_amount' => 50.00,
        'total' => 1735.00,
    ]);

    $this->invoiceService = app(InvoiceService::class);
    $this->numberGenerator = app(InvoiceNumberGenerator::class);
});

/*
|--------------------------------------------------------------------------
| 1. Invoice Number Generation & Uniqueness
|--------------------------------------------------------------------------
*/

test('invoice number generator generates valid collision-safe format', function () {
    $now = Carbon::parse('2026-09-07 12:00:00');
    $invoiceNumber = $this->numberGenerator->generate($now);

    expect($invoiceNumber)->toMatch('/^INV-20260907-[A-Z0-9]{6}$/');
});

test('invoice numbers are unique across multiple creations', function () {
    $numbers = [];
    for ($i = 0; $i < 10; $i++) {
        $num = $this->numberGenerator->generate();
        expect(in_array($num, $numbers))->toBeFalse();
        $numbers[] = $num;
    }
    expect(count($numbers))->toBe(10);
});

/*
|--------------------------------------------------------------------------
| 2. Invoice Creation & Historical Snapshots
|--------------------------------------------------------------------------
*/

test('creates invoice with complete historical snapshots and correct associations', function () {
    $invoice = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->order_id)->toBe($this->paidOrder->id)
        ->and($invoice->currency)->toBe('INR')
        ->and((float) $invoice->subtotal)->toBe(1700.00)
        ->and((float) $invoice->tax_amount)->toBe(85.00)
        ->and((float) $invoice->discount_amount)->toBe(50.00)
        ->and((float) $invoice->shipping_amount)->toBe(0.00)
        ->and((float) $invoice->grand_total)->toBe(1735.00)
        ->and($invoice->payment_status_snapshot)->toBe('paid')
        ->and($invoice->order_status_snapshot)->toBe('processing');

    // Verify customer snapshot
    expect($invoice->customer_snapshot)->toBeArray()
        ->and($invoice->customer_snapshot['customer_id'])->toBe($this->customer->id)
        ->and($invoice->customer_snapshot['name'])->toBe('Kavita Rao')
        ->and($invoice->customer_snapshot['phone'])->toBe('+919876543210');

    // Verify shipping snapshot
    expect($invoice->shipping_address_snapshot)->toBeArray()
        ->and($invoice->shipping_address_snapshot['city'])->toBe('Delhi')
        ->and($invoice->shipping_address_snapshot['postal_code'])->toBe('110082');

    // Verify items snapshot from authoritative OrderItem
    expect($invoice->items_snapshot)->toBeArray()
        ->and(count($invoice->items_snapshot))->toBe(1)
        ->and($invoice->items_snapshot[0]['product_name'])->toBe('Monstera Deliciosa')
        ->and($invoice->items_snapshot[0]['sku'])->toBe('MON-DEL-01')
        ->and($invoice->items_snapshot[0]['quantity'])->toBe(2)
        ->and((float) $invoice->items_snapshot[0]['price'])->toBe(850.00)
        ->and((float) $invoice->items_snapshot[0]['total'])->toBe(1735.00);

    // Verify relationship
    expect($this->paidOrder->fresh()->invoice->id)->toBe($invoice->id)
        ->and($invoice->order->id)->toBe($this->paidOrder->id);
});

/*
|--------------------------------------------------------------------------
| 3. Invoice Immutability & Duplicate Prevention
|--------------------------------------------------------------------------
*/

test('calling getOrCreateInvoiceForOrder multiple times returns existing invoice without duplicating', function () {
    $invoice1 = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);
    $invoice2 = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);

    expect($invoice1->id)->toBe($invoice2->id)
        ->and($invoice1->invoice_number)->toBe($invoice2->invoice_number)
        ->and(Invoice::where('order_id', $this->paidOrder->id)->count())->toBe(1);
});

test('invoice snapshot is completely immutable even if customer or product changes later', function () {
    $invoice = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);

    // Mutate live customer and product records
    $this->customer->update(['name' => 'Changed Customer Name', 'phone' => '+910000000000']);
    $this->product->update(['name' => 'Changed Product Title']);
    $this->variant->update(['sku' => 'CHANGED-SKU']);

    // Retrieve fresh invoice from database
    $freshInvoice = Invoice::find($invoice->id);

    // Verify historical snapshots are completely unchanged
    expect($freshInvoice->customer_snapshot['name'])->toBe('Kavita Rao')
        ->and($freshInvoice->customer_snapshot['phone'])->toBe('+919876543210')
        ->and($freshInvoice->items_snapshot[0]['product_name'])->toBe('Monstera Deliciosa')
        ->and($freshInvoice->items_snapshot[0]['sku'])->toBe('MON-DEL-01');
});

/*
|--------------------------------------------------------------------------
| 4. Tax Invoice Eligibility Rules
|--------------------------------------------------------------------------
*/

test('unpaid pending order cannot generate a tax invoice', function () {
    $unpaidOrder = Order::create([
        'order_number' => 'ORD-20260907-UNPAID',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    expect($this->invoiceService->canGenerateInvoice($unpaidOrder))->toBeFalse();

    expect(fn () => $this->invoiceService->getOrCreateInvoiceForOrder($unpaidOrder))
        ->toThrow(IneligibleForInvoiceException::class);
});

test('order with failed payment cannot generate a tax invoice', function () {
    $failedOrder = Order::create([
        'order_number' => 'ORD-20260907-FAILED',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::FAILED,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    expect($this->invoiceService->canGenerateInvoice($failedOrder))->toBeFalse();

    expect(fn () => $this->invoiceService->getOrCreateInvoiceForOrder($failedOrder))
        ->toThrow(IneligibleForInvoiceException::class);
});

test('cancelled order without existing invoice cannot generate tax invoice', function () {
    $cancelledOrder = Order::create([
        'order_number' => 'ORD-20260907-CANCELLED',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::CANCELLED,
        'payment_status' => PaymentStatus::CANCELLED,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    expect($this->invoiceService->canGenerateInvoice($cancelledOrder))->toBeFalse();

    expect(fn () => $this->invoiceService->getOrCreateInvoiceForOrder($cancelledOrder))
        ->toThrow(IneligibleForInvoiceException::class);
});

test('historical invoice remains retrievable even if order is later cancelled', function () {
    // Generate invoice while order is paid
    $invoice = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);

    // Cancel the order later
    $this->paidOrder->update(['status' => OrderStatus::CANCELLED]);

    // Historical invoice must still be retrievable
    expect($this->invoiceService->canGenerateInvoice($this->paidOrder))->toBeTrue();
    $retrieved = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);
    expect($retrieved->id)->toBe($invoice->id);
});

/*
|--------------------------------------------------------------------------
| 5. Order Deletion Protection (restrictOnDelete)
|--------------------------------------------------------------------------
*/

test('orders with invoices cannot be hard deleted due to restrict foreign key constraint', function () {
    $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);

    // Attempting to force delete (hard delete) the order with an invoice must trigger database constraint violation
    expect(function () {
        $this->paidOrder->forceDelete();
    })->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| 6. Customer Invoice Access & IDOR Protection
|--------------------------------------------------------------------------
*/

test('customer can view and download own invoice PDF', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.orders.invoice', $this->paidOrder->order_number));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect(Invoice::where('order_id', $this->paidOrder->id)->exists())->toBeTrue();
});

test('customer cannot access another customer invoice returning 404', function () {
    $otherCustomer = Customer::factory()->create([
        'name' => 'Attacker Customer',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($otherCustomer, 'customer');
    session(['customer_auth_token_version' => $otherCustomer->auth_token_version]);

    $response = $this->get(route('account.orders.invoice', $this->paidOrder->order_number));

    $response->assertNotFound();
});

test('guest is redirected to login when accessing customer invoice route', function () {
    $response = $this->get(route('account.orders.invoice', $this->paidOrder->order_number));

    $response->assertRedirect(route('customer.login'));
});

test('customer accessing invoice for unpaid order receives 404', function () {
    $unpaidOrder = Order::create([
        'order_number' => 'ORD-20260907-CUSTUNP',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $response = $this->get(route('account.orders.invoice', $unpaidOrder->order_number));

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| 7. Admin Invoice Access & RBAC
|--------------------------------------------------------------------------
*/

test('authorized admin with orders.view can access and stream invoice PDF', function () {
    Permission::findOrCreate('orders.view', 'admin');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('orders.view');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.orders.invoice', $this->paidOrder));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect(Invoice::where('order_id', $this->paidOrder->id)->exists())->toBeTrue();
});

test('unauthorized admin without orders.view receives 403 Forbidden', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.orders.invoice', $this->paidOrder));

    $response->assertForbidden();
});

test('admin cannot bypass eligibility rule for unpaid order returning 404', function () {
    Permission::findOrCreate('orders.view', 'admin');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('orders.view');

    $unpaidOrder = Order::create([
        'order_number' => 'ORD-20260907-ADMUNP',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.orders.invoice', $unpaidOrder));

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| 8. PDF Content & Sensitive Data Exclusion
|--------------------------------------------------------------------------
*/

test('pdf invoice renders and contains verified business details and excludes sensitive secrets', function () {
    $invoice = $this->invoiceService->getOrCreateInvoiceForOrder($this->paidOrder);
    $pdfOutput = $this->invoiceService->renderPdf($invoice);

    expect(strlen($pdfOutput))->toBeGreaterThan(1000)
        ->and(str_starts_with($pdfOutput, '%PDF-'))->toBeTrue();

    // Verify HTML view rendering
    $viewHtml = view('invoices.pdf', [
        'invoice' => $invoice,
        'business' => $this->invoiceService->getBusinessDetails(),
    ])->render();

    // Contains verified business information
    expect($viewHtml)->toContain('SUGANDHA FARMS AND NURSERY')
        ->and($viewHtml)->toContain('098111 14365')
        ->and($viewHtml)->toContain('Mann Enclave, near Gurukul')
        ->and($viewHtml)->toContain($invoice->invoice_number)
        ->and($viewHtml)->toContain($this->paidOrder->order_number)
        ->and($viewHtml)->toContain('Monstera Deliciosa')
        ->and($viewHtml)->toContain('1,735.00');

    // Strict data scrubbing: NEVER invent or include GSTIN, fake registrations, card credentials
    expect($viewHtml)->not->toContain('GSTIN')
        ->and($viewHtml)->not->toContain('CVV')
        ->and($viewHtml)->not->toContain('card_number')
        ->and($viewHtml)->not->toContain('secret')
        ->and($viewHtml)->not->toContain('null_test_signature');
});

/*
|--------------------------------------------------------------------------
| 9. UI Integration Tests
|--------------------------------------------------------------------------
*/

test('customer order show view displays tax invoice link only when eligible', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    // Paid order should show download invoice link
    $paidResponse = $this->get(route('account.orders.show', $this->paidOrder->order_number));
    $paidResponse->assertOk();
    $paidResponse->assertSee(route('account.orders.invoice', $this->paidOrder->order_number));
    $paidResponse->assertSee('Download Tax Invoice (PDF)');

    // Unpaid order should not show download invoice link
    $unpaidOrder = Order::create([
        'order_number' => 'ORD-20260907-UISHOW',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    $unpaidResponse = $this->get(route('account.orders.show', $unpaidOrder->order_number));
    $unpaidResponse->assertOk();
    $unpaidResponse->assertDontSee(route('account.orders.invoice', $unpaidOrder->order_number));
    $unpaidResponse->assertDontSee('Download Tax Invoice (PDF)');
});

test('admin order show view displays invoice actions only when eligible', function () {
    Permission::findOrCreate('orders.view', 'admin');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('orders.view');

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    // Paid order shows View Invoice (PDF)
    $paidResponse = $this->get(route('admin.orders.show', $this->paidOrder));
    $paidResponse->assertOk();
    $paidResponse->assertSee(route('admin.orders.invoice', $this->paidOrder));
    $paidResponse->assertSee('View Invoice (PDF)');

    // Unpaid order does not show View Invoice (PDF) button
    $unpaidOrder = Order::create([
        'order_number' => 'ORD-20260907-ADMUISHOW',
        'customer_id' => $this->customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'subtotal' => 850.00,
        'grand_total' => 850.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    $unpaidResponse = $this->get(route('admin.orders.show', $unpaidOrder));
    $unpaidResponse->assertOk();
    $unpaidResponse->assertDontSee(route('admin.orders.invoice', $unpaidOrder));
    $unpaidResponse->assertSee('Tax invoice unavailable (order unpaid/ineligible)');
});
