<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Exceptions\Payment\DuplicatePaymentException;
use App\Exceptions\Payment\InvalidPaymentTransitionException;
use App\Exceptions\Payment\PaymentAmountMismatchException;
use App\Exceptions\Payment\PaymentGatewayException;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Audit\AuditLogger;
use App\Services\Payment\DTO\PaymentInitiationRequest;
use App\Services\Payment\DTO\PaymentInitiationResponse;
use App\Services\Payment\DTO\PaymentStatusResponse;
use App\Services\Payment\DTO\PaymentVerificationRequest;
use App\Services\Payment\DTO\PaymentVerificationResponse;
use App\Services\Payment\Gateways\NullPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentTransactionNumberGenerator;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->customer = Customer::factory()->create([
        'name' => 'Aditi Sharma',
        'phone' => '+919811122334',
        'email' => 'aditi@example.com',
        'is_active' => true,
    ]);

    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Aditi Sharma',
        'phone' => '+919811122334',
        'address_line_1' => 'Flat 101, Green Heights',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
        'is_default' => true,
    ]);

    $this->order = Order::create([
        'order_number' => 'ORD-20260907-TEST01',
        'customer_id' => $this->customer->id,
        'customer_name' => 'Aditi Sharma',
        'customer_phone' => '+919811122334',
        'customer_email' => 'aditi@example.com',
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 1500.00,
        'tax_amount' => 75.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 0.00,
        'grand_total' => 1575.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    $this->paymentService = app(PaymentService::class);
    $this->gatewayManager = app(PaymentGatewayManager::class);
});

/*
|--------------------------------------------------------------------------
| 1. PaymentStatus Enum & Transition State Machine Tests
|--------------------------------------------------------------------------
*/

test('payment status enum supports all required lifecycle cases', function () {
    expect(PaymentStatus::PENDING->value)->toBe('pending')
        ->and(PaymentStatus::AUTHORIZED->value)->toBe('authorized')
        ->and(PaymentStatus::PAID->value)->toBe('paid')
        ->and(PaymentStatus::FAILED->value)->toBe('failed')
        ->and(PaymentStatus::CANCELLED->value)->toBe('cancelled')
        ->and(PaymentStatus::EXPIRED->value)->toBe('expired')
        ->and(PaymentStatus::REFUNDED->value)->toBe('refunded');
});

test('payment status state machine enforces valid and invalid transitions', function () {
    // PENDING transitions
    expect(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::AUTHORIZED))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::PAID))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::FAILED))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::CANCELLED))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::EXPIRED))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::REFUNDED))->toBeFalse();

    // AUTHORIZED transitions
    expect(PaymentStatus::AUTHORIZED->canTransitionTo(PaymentStatus::PAID))->toBeTrue()
        ->and(PaymentStatus::AUTHORIZED->canTransitionTo(PaymentStatus::FAILED))->toBeTrue()
        ->and(PaymentStatus::AUTHORIZED->canTransitionTo(PaymentStatus::CANCELLED))->toBeTrue()
        ->and(PaymentStatus::AUTHORIZED->canTransitionTo(PaymentStatus::PENDING))->toBeFalse();

    // PAID transitions
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::REFUNDED))->toBeTrue()
        ->and(PaymentStatus::PAID->canTransitionTo(PaymentStatus::PENDING))->toBeFalse()
        ->and(PaymentStatus::PAID->canTransitionTo(PaymentStatus::FAILED))->toBeFalse()
        ->and(PaymentStatus::PAID->canTransitionTo(PaymentStatus::CANCELLED))->toBeFalse();

    // FAILED transitions: allows retry back to pending
    expect(PaymentStatus::FAILED->canTransitionTo(PaymentStatus::PENDING))->toBeTrue()
        ->and(PaymentStatus::FAILED->canTransitionTo(PaymentStatus::PAID))->toBeFalse();

    // Terminal states cannot transition
    expect(PaymentStatus::CANCELLED->canTransitionTo(PaymentStatus::PAID))->toBeFalse()
        ->and(PaymentStatus::EXPIRED->canTransitionTo(PaymentStatus::PAID))->toBeFalse()
        ->and(PaymentStatus::REFUNDED->canTransitionTo(PaymentStatus::PAID))->toBeFalse();

    // Idempotent same-state transitions are always true
    expect(PaymentStatus::PAID->canTransitionTo(PaymentStatus::PAID))->toBeTrue()
        ->and(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::PENDING))->toBeTrue();

    // isTerminal checks
    expect(PaymentStatus::CANCELLED->isTerminal())->toBeTrue()
        ->and(PaymentStatus::EXPIRED->isTerminal())->toBeTrue()
        ->and(PaymentStatus::REFUNDED->isTerminal())->toBeTrue()
        ->and(PaymentStatus::PENDING->isTerminal())->toBeFalse()
        ->and(PaymentStatus::PAID->isTerminal())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| 2. DTO Creation & Attributes Tests
|--------------------------------------------------------------------------
*/

test('payment DTOs can be instantiated and maintain type safety', function () {
    $initReq = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'PAY-20260907-DTO001',
        amount: '1575.00',
        currency: 'INR',
        returnUrl: 'http://localhost/return',
        customer: $this->customer,
        metadata: ['channel' => 'web']
    );

    expect($initReq->transactionNumber)->toBe('PAY-20260907-DTO001')
        ->and($initReq->amount)->toBe('1575.00')
        ->and($initReq->currency)->toBe('INR')
        ->and($initReq->metadata['channel'])->toBe('web');

    $initRes = new PaymentInitiationResponse(
        success: true,
        transactionNumber: 'PAY-20260907-DTO001',
        redirectUrl: 'http://localhost/checkout/pay',
        gatewayReference: 'GW-REF-999',
        errorMessage: null,
        rawPayload: ['foo' => 'bar']
    );

    expect($initRes->success)->toBeTrue()
        ->and($initRes->redirectUrl)->toBe('http://localhost/checkout/pay')
        ->and($initRes->gatewayReference)->toBe('GW-REF-999');

    $verifyReq = new PaymentVerificationRequest(
        transactionNumber: 'PAY-20260907-DTO001',
        payload: ['status' => 'success'],
        headers: ['X-Signature' => 'sig123']
    );

    expect($verifyReq->transactionNumber)->toBe('PAY-20260907-DTO001')
        ->and($verifyReq->payload['status'])->toBe('success')
        ->and($verifyReq->headers['X-Signature'])->toBe('sig123');

    $verifyRes = new PaymentVerificationResponse(
        success: true,
        status: PaymentStatus::PAID,
        gatewayTransactionId: 'TXN-ABC-123',
        amount: '1575.00',
        currency: 'INR',
        paymentMethod: 'upi',
        rawPayload: ['raw' => 'data']
    );

    expect($verifyRes->success)->toBeTrue()
        ->and($verifyRes->status)->toBe(PaymentStatus::PAID)
        ->and($verifyRes->gatewayTransactionId)->toBe('TXN-ABC-123');

    $statusRes = new PaymentStatusResponse(
        status: PaymentStatus::PENDING,
        gatewayTransactionId: 'TXN-ABC-123',
        amount: '1575.00'
    );

    expect($statusRes->status)->toBe(PaymentStatus::PENDING);
});

/*
|--------------------------------------------------------------------------
| 3. NullPaymentGateway Implementation Tests
|--------------------------------------------------------------------------
*/

test('null payment gateway initiates simulated payment correctly', function () {
    $gateway = new NullPaymentGateway;

    expect($gateway->getIdentifier())->toBe('null');

    $request = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'PAY-20260907-NULL01',
        amount: '1575.00',
        currency: 'INR',
        returnUrl: 'http://localhost/return'
    );

    $response = $gateway->initiatePayment($request);

    expect($response->success)->toBeTrue()
        ->and($response->transactionNumber)->toBe('PAY-20260907-NULL01')
        ->and($response->gatewayReference)->not->toBeEmpty()
        ->and($response->redirectUrl)->toContain('gateway=null');
});

test('null payment gateway verifies payment simulating paid, failed, and cancelled states', function () {
    $gateway = new NullPaymentGateway;

    // Default simulation: PAID
    $paidResponse = $gateway->verifyPayment(new PaymentVerificationRequest(
        transactionNumber: 'PAY-20260907-NULL01',
        payload: ['amount' => '1575.00']
    ));

    expect($paidResponse->success)->toBeTrue()
        ->and($paidResponse->status)->toBe(PaymentStatus::PAID)
        ->and($paidResponse->gatewayTransactionId)->not->toBeEmpty();

    // Simulated FAILED
    $failedResponse = $gateway->verifyPayment(new PaymentVerificationRequest(
        transactionNumber: 'PAY-20260907-NULL01',
        payload: [
            'simulated_status' => 'failed',
            'failure_code' => 'CARD_DECLINED',
            'failure_message' => 'Card was declined by issuing bank.',
        ]
    ));

    expect($failedResponse->success)->toBeFalse()
        ->and($failedResponse->status)->toBe(PaymentStatus::FAILED)
        ->and($failedResponse->failureCode)->toBe('CARD_DECLINED')
        ->and($failedResponse->failureMessage)->toBe('Card was declined by issuing bank.');

    // Simulated CANCELLED
    $cancelledResponse = $gateway->verifyPayment(new PaymentVerificationRequest(
        transactionNumber: 'PAY-20260907-NULL01',
        payload: [
            'simulated_status' => 'cancelled',
        ]
    ));

    expect($cancelledResponse->success)->toBeFalse()
        ->and($cancelledResponse->status)->toBe(PaymentStatus::CANCELLED);
});

/*
|--------------------------------------------------------------------------
| 4. PaymentGatewayManager Resolution Tests
|--------------------------------------------------------------------------
*/

test('payment gateway manager resolves default null gateway and rejects unsupported drivers', function () {
    $gateway = $this->gatewayManager->gateway();
    expect($gateway)->toBeInstanceOf(NullPaymentGateway::class)
        ->and($gateway->getIdentifier())->toBe('null');

    // Explicit null gateway
    $explicitNull = $this->gatewayManager->gateway('null');
    expect($explicitNull)->toBeInstanceOf(NullPaymentGateway::class);

    // Unsupported gateway driver
    expect(fn () => $this->gatewayManager->gateway('unsupported_gateway'))
        ->toThrow(PaymentGatewayException::class, 'Unsupported payment gateway driver: [unsupported_gateway].');
});

/*
|--------------------------------------------------------------------------
| 5. PaymentTransactionNumberGenerator Tests
|--------------------------------------------------------------------------
*/

test('payment transaction number generator creates unique formatted identifiers', function () {
    $generator = app(PaymentTransactionNumberGenerator::class);

    $num1 = $generator->generate();
    $num2 = $generator->generate();

    expect($num1)->toMatch('/^PAY-\d{8}-[A-Z0-9]{6}$/')
        ->and($num2)->toMatch('/^PAY-\d{8}-[A-Z0-9]{6}$/')
        ->and($num1)->not->toBe($num2);
});

/*
|--------------------------------------------------------------------------
| 6. PaymentService Initiation Tests
|--------------------------------------------------------------------------
*/

test('payment service initiates payment with exact order grand total and creates pending transaction', function () {
    $response = $this->paymentService->initiatePayment(
        order: $this->order,
        gatewayName: 'null',
        customer: $this->customer,
        metadata: ['custom_note' => 'test order']
    );

    expect($response->success)->toBeTrue()
        ->and($response->transactionNumber)->not->toBeEmpty();

    $this->assertDatabaseHas('payment_transactions', [
        'transaction_number' => $response->transactionNumber,
        'order_id' => $this->order->id,
        'amount' => '1575.00',
        'currency' => 'INR',
        'gateway' => 'null',
        'status' => 'pending',
    ]);

    $transaction = PaymentTransaction::where('transaction_number', $response->transactionNumber)->first();
    expect($transaction->isPending())->toBeTrue()
        ->and($transaction->isPaid())->toBeFalse();
});

test('payment service prevents initiation on an already paid order', function () {
    $this->order->update(['payment_status' => PaymentStatus::PAID]);

    expect(fn () => $this->paymentService->initiatePayment($this->order))
        ->toThrow(DomainException::class, 'already paid');
});

test('payment service enforces idempotency key on initiation', function () {
    $idempotencyKey = 'idemp_key_abc_123';

    $res1 = $this->paymentService->initiatePayment(
        order: $this->order,
        idempotencyKey: $idempotencyKey
    );

    // Call second time with same idempotency key for the same order
    $res2 = $this->paymentService->initiatePayment(
        order: $this->order,
        idempotencyKey: $idempotencyKey
    );

    expect($res2->transactionNumber)->toBe($res1->transactionNumber);
    expect(PaymentTransaction::where('idempotency_key', $idempotencyKey)->count())->toBe(1);

    // Attempting to reuse the same idempotency key with a DIFFERENT order must throw DuplicatePaymentException
    $anotherOrder = Order::create([
        'order_number' => 'ORD-20260907-ANOTHER',
        'customer_id' => $this->customer->id,
        'customer_name' => 'Aditi Sharma',
        'customer_phone' => '+919811122334',
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 500.00,
        'tax_amount' => 25.00,
        'shipping_amount' => 0.00,
        'discount_amount' => 0.00,
        'grand_total' => 525.00,
        'shipping_address_json' => $this->address->toArray(),
        'billing_address_json' => $this->address->toArray(),
    ]);

    expect(fn () => $this->paymentService->initiatePayment($anotherOrder, idempotencyKey: $idempotencyKey))
        ->toThrow(DuplicatePaymentException::class);
});

/*
|--------------------------------------------------------------------------
| 7. PaymentService Verification & Lifecycle State Synchronization Tests
|--------------------------------------------------------------------------
*/

test('payment service verifies payment, transitions transaction and order to paid, and records status history', function () {
    $initiation = $this->paymentService->initiatePayment(
        order: $this->order,
        customer: $this->customer
    );

    $verification = $this->paymentService->processPaymentVerification(
        transaction: $initiation->transactionNumber,
        payload: [
            'amount' => '1575.00',
            'simulated_status' => 'paid',
            'gateway_transaction_id' => 'NULL-CAPTURED-999',
            'payment_method' => 'upi',
        ]
    );

    expect($verification->success)->toBeTrue()
        ->and($verification->status)->toBe(PaymentStatus::PAID);

    $this->order->refresh();
    $transaction = PaymentTransaction::where('transaction_number', $initiation->transactionNumber)->first();

    expect($transaction->status)->toBe(PaymentStatus::PAID)
        ->and($transaction->isPaid())->toBeTrue()
        ->and($transaction->paid_at)->not->toBeNull()
        ->and($transaction->gateway_transaction_id)->toBe('NULL-CAPTURED-999')
        ->and($transaction->payment_method)->toBe('upi');

    // Order status synchronized
    expect($this->order->payment_status)->toBe(PaymentStatus::PAID)
        ->and($this->order->status)->toBe(OrderStatus::PROCESSING);

    // Order status history created
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $this->order->id,
        'to_status' => OrderStatus::PROCESSING->value,
    ]);
});

test('payment service verification is idempotent when called multiple times for already paid transaction', function () {
    $initiation = $this->paymentService->initiatePayment(order: $this->order);

    // First verification: captures payment
    $v1 = $this->paymentService->processPaymentVerification(
        transaction: $initiation->transactionNumber,
        payload: ['amount' => '1575.00', 'simulated_status' => 'paid']
    );

    expect($v1->success)->toBeTrue();

    // Second verification call (duplicate webhook / callback retry)
    $v2 = $this->paymentService->processPaymentVerification(
        transaction: $initiation->transactionNumber,
        payload: ['amount' => '1575.00', 'simulated_status' => 'paid']
    );

    expect($v2->success)->toBeTrue()
        ->and($v2->status)->toBe(PaymentStatus::PAID);

    // Order remains PROCESSING and PAID without duplicate side effects
    $this->order->refresh();
    expect($this->order->payment_status)->toBe(PaymentStatus::PAID);
});

test('payment service throws PaymentAmountMismatchException when gateway reports differing amount', function () {
    $initiation = $this->paymentService->initiatePayment(order: $this->order);

    expect(fn () => $this->paymentService->processPaymentVerification(
        transaction: $initiation->transactionNumber,
        payload: [
            'amount' => '1000.00', // Mismatch! Expected 1575.00
            'simulated_status' => 'paid',
        ]
    ))->toThrow(PaymentAmountMismatchException::class);

    $transaction = PaymentTransaction::where('transaction_number', $initiation->transactionNumber)->first();
    expect($transaction->status)->toBe(PaymentStatus::FAILED)
        ->and($transaction->failure_code)->toBe('AMOUNT_MISMATCH');

    $this->order->refresh();
    expect($this->order->payment_status)->toBe(PaymentStatus::FAILED);
});

test('decoupled lifecycle: payment failure updates payment status but does NOT cancel order or release stock', function () {
    $initiation = $this->paymentService->initiatePayment(order: $this->order);

    $verification = $this->paymentService->processPaymentVerification(
        transaction: $initiation->transactionNumber,
        payload: [
            'simulated_status' => 'failed',
            'failure_code' => 'INSUFFICIENT_FUNDS',
            'failure_message' => 'Not enough balance.',
        ]
    );

    expect($verification->success)->toBeFalse()
        ->and($verification->status)->toBe(PaymentStatus::FAILED);

    $this->order->refresh();
    $transaction = PaymentTransaction::where('transaction_number', $initiation->transactionNumber)->first();

    expect($transaction->status)->toBe(PaymentStatus::FAILED)
        ->and($transaction->isFailed())->toBeTrue()
        ->and($transaction->failure_code)->toBe('INSUFFICIENT_FUNDS')
        ->and($transaction->failed_at)->not->toBeNull();

    // Order payment status is FAILED, but order status remains PENDING (NOT cancelled)
    expect($this->order->payment_status)->toBe(PaymentStatus::FAILED)
        ->and($this->order->status)->toBe(OrderStatus::PENDING);
});

test('payment service cancel and expire update payment transaction states safely', function () {
    // Cancel
    $initiation1 = $this->paymentService->initiatePayment(order: $this->order);
    $cancelledTxn = $this->paymentService->cancelPayment($initiation1->transactionNumber, 'User navigated back.');

    expect($cancelledTxn->status)->toBe(PaymentStatus::CANCELLED)
        ->and($cancelledTxn->isCancelled())->toBeTrue()
        ->and($cancelledTxn->cancelled_at)->not->toBeNull();

    // Expire
    $initiation2 = $this->paymentService->initiatePayment(order: $this->order);
    $expiredTxn = $this->paymentService->expirePayment($initiation2->transactionNumber, 'Gateway session timed out.');

    expect($expiredTxn->status)->toBe(PaymentStatus::EXPIRED)
        ->and($expiredTxn->isExpired())->toBeTrue();
});

test('payment service throws InvalidPaymentTransitionException when attempting illegal transition', function () {
    $initiation = $this->paymentService->initiatePayment(order: $this->order);
    $this->paymentService->cancelPayment($initiation->transactionNumber);

    // Cancelled is terminal. Trying to verify as paid must throw InvalidPaymentTransitionException
    expect(fn () => $this->paymentService->processPaymentVerification(
        transaction: $initiation->transactionNumber,
        payload: ['simulated_status' => 'paid']
    ))->toThrow(InvalidPaymentTransitionException::class);
});

/*
|--------------------------------------------------------------------------
| 8. AuditLogger Payment Sensitivity Scrubbing Tests
|--------------------------------------------------------------------------
*/

test('audit logger scrubs payment sensitive fields in audit payloads', function () {
    $logger = app(AuditLogger::class);

    $payloadWithSecrets = [
        'transaction_number' => 'PAY-20260907-SENSITIVE',
        'card_number' => '4111111111111111',
        'cvv' => '123',
        'pin' => '9999',
        'signature' => 'sha256_mock_signature',
        'auth_header' => 'Bearer secret_token',
        'api_key' => 'live_sec_key_xyz',
        'nested' => [
            'pan' => '1234567890123456',
            'client_secret' => 'super_secret',
            'safe_amount' => '1575.00',
        ],
    ];

    $scrubbed = $logger->scrubSensitiveData($payloadWithSecrets);

    expect($scrubbed['transaction_number'])->toBe('PAY-20260907-SENSITIVE')
        ->and($scrubbed['card_number'])->toBe('[REDACTED]')
        ->and($scrubbed['cvv'])->toBe('[REDACTED]')
        ->and($scrubbed['pin'])->toBe('[REDACTED]')
        ->and($scrubbed['signature'])->toBe('[REDACTED]')
        ->and($scrubbed['auth_header'])->toBe('[REDACTED]')
        ->and($scrubbed['api_key'])->toBe('[REDACTED]')
        ->and($scrubbed['nested']['pan'])->toBe('[REDACTED]')
        ->and($scrubbed['nested']['client_secret'])->toBe('[REDACTED]')
        ->and($scrubbed['nested']['safe_amount'])->toBe('1575.00');
});

/*
|--------------------------------------------------------------------------
| 9. Admin Order View Integration Tests
|--------------------------------------------------------------------------
*/

test('admin order details page renders payment transactions table', function () {
    // Setup admin with permission
    Permission::findOrCreate('orders.view', 'admin');
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('orders.view');

    // Create a transaction for the order
    PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'PAY-20260907-ADM001',
        'gateway' => 'null',
        'gateway_transaction_id' => 'NULL-REF-123456',
        'amount' => 1575.00,
        'currency' => 'INR',
        'status' => PaymentStatus::PAID,
        'payment_method' => 'simulated_null',
        'paid_at' => now(),
    ]);

    $this->actingAs($admin, 'admin');
    session(['admin_auth_token_version' => $admin->auth_token_version]);

    $response = $this->get(route('admin.orders.show', $this->order));

    $response->assertStatus(200)
        ->assertSee('Payment Transactions')
        ->assertSee('PAY-20260907-ADM001')
        ->assertSee('NULL-REF-123456')
        ->assertSee('₹1,575.00')
        ->assertSee('simulated_null')
        ->assertSee('paid');
});
