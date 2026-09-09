<?php

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Exceptions\Payment\PaymentGatewayException;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentInitiationRequest;
use App\Services\Payment\DTO\PaymentVerificationRequest;
use App\Services\Payment\Gateways\NullPaymentGateway;
use App\Services\Payment\Gateways\RazorpayPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentService;
use Razorpay\Api\Api;

beforeEach(function () {
    $this->testKeyId = 'rzp_test_mockKeyId12345';
    $this->testKeySecret = 'testSecretKey9876543210Abc';

    config([
        'services.razorpay.key_id' => $this->testKeyId,
        'services.razorpay.key_secret' => $this->testKeySecret,
        'payment.default' => 'razorpay',
    ]);

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
        'order_number' => 'ORD-20260909-RZP01',
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
| 1. Gateway Resolution & Contract Compliance Tests
|--------------------------------------------------------------------------
*/

test('1. Razorpay gateway can be resolved through PaymentGatewayManager', function () {
    $gateway = $this->gatewayManager->gateway('razorpay');

    expect($gateway)->toBeInstanceOf(RazorpayPaymentGateway::class)
        ->and($gateway)->toBeInstanceOf(PaymentGatewayInterface::class)
        ->and($gateway->getIdentifier())->toBe('razorpay')
        ->and($gateway->getKeyId())->toBe($this->testKeyId);
});

test('2. Razorpay order creation uses the server-side amount and converts to paise correctly', function () {
    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();

    $orderResource->shouldReceive('create')
        ->once()
        ->withArgs(function ($payload) {
            return $payload['amount'] === 157500 // 1575.00 * 100 paise
                && $payload['currency'] === 'INR'
                && $payload['receipt'] === 'TXN-TEST-001'
                && $payload['notes']['order_number'] === 'ORD-20260909-RZP01';
        })
        ->andReturn((object) ['id' => 'order_rzp_test_123456']);

    $mockApi->order = $orderResource;

    $gateway = new RazorpayPaymentGateway($mockApi, $this->testKeyId, $this->testKeySecret);

    $request = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'TXN-TEST-001',
        amount: '1575.00',
        currency: 'INR',
        returnUrl: 'http://localhost/checkout/complete'
    );

    $response = $gateway->initiatePayment($request);

    expect($response->success)->toBeTrue()
        ->and($response->gatewayReference)->toBe('order_rzp_test_123456')
        ->and($response->rawPayload['amount_in_paise'])->toBe(157500)
        ->and($response->rawPayload['currency'])->toBe('INR')
        ->and($response->rawPayload['key_id'])->toBe($this->testKeyId)
        ->and(array_key_exists('key_secret', $response->rawPayload))->toBeFalse();
});

test('3. INR currency is enforced during Razorpay order creation', function () {
    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();

    $orderResource->shouldReceive('create')
        ->once()
        ->withArgs(fn ($payload) => $payload['currency'] === 'INR')
        ->andReturn((object) ['id' => 'order_rzp_curr_789']);

    $mockApi->order = $orderResource;
    $gateway = new RazorpayPaymentGateway($mockApi, $this->testKeyId, $this->testKeySecret);

    $request = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'TXN-CURR-001',
        amount: '100.00',
        currency: 'INR',
        returnUrl: 'http://localhost/checkout/complete'
    );

    $response = $gateway->initiatePayment($request);
    expect($response->rawPayload['currency'])->toBe('INR');
});

test('4. Razorpay order ID is stored in the existing payment transaction record via PaymentService', function () {
    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();
    $orderResource->shouldReceive('create')
        ->once()
        ->andReturn((object) ['id' => 'order_rzp_stored_999']);
    $mockApi->order = $orderResource;

    /** @var RazorpayPaymentGateway $gateway */
    $gateway = $this->gatewayManager->gateway('razorpay');
    $gateway->setApi($mockApi);

    $initiation = $this->paymentService->initiatePayment(
        order: $this->order,
        gatewayName: 'razorpay',
        customer: $this->customer
    );

    $transaction = PaymentTransaction::where('transaction_number', $initiation->transactionNumber)->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->gateway)->toBe('razorpay')
        ->and($transaction->gateway_transaction_id)->toBe('order_rzp_stored_999')
        ->and($transaction->payload['gateway_reference'])->toBe('order_rzp_stored_999')
        ->and($transaction->payload['gateway_response']['key_id'])->toBe($this->testKeyId)
        ->and(isset($transaction->payload['gateway_response']['key_secret']))->toBeFalse();
});

test('5. Razorpay Key Secret is never returned to frontend or exposed in payloads', function () {
    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $mockApi = Mockery::mock(Api::class);
    $mockApi->order = Mockery::mock(['create' => (object) ['id' => 'order_secret_check_111']]);
    $gateway->setApi($mockApi);

    $request = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'TXN-SECRET-001',
        amount: '500.00',
        currency: 'INR',
        returnUrl: 'http://localhost/checkout/complete'
    );

    $response = $gateway->initiatePayment($request);
    $jsonEncoded = json_encode($response->rawPayload);

    expect($jsonEncoded)->not->toContain($this->testKeySecret)
        ->and(array_key_exists('key_secret', $response->rawPayload))->toBeFalse();
});

test('6. Key ID is safely exposed for frontend Razorpay checkout', function () {
    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);
    expect($gateway->getKeyId())->toBe($this->testKeyId);
});

/*
|--------------------------------------------------------------------------
| 2. Server-Side Signature Verification Tests
|--------------------------------------------------------------------------
*/

test('7. Successful signature verification succeeds and matches HMAC-SHA256', function () {
    $serverOrderId = 'order_rzp_valid_123';
    $paymentId = 'pay_rzp_valid_456';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-VERIFY-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    // Generate authoritative signature: HMAC-SHA256(server_order_id + "|" + payment_id, secret)
    $validSignature = hash_hmac('sha256', $serverOrderId.'|'.$paymentId, $this->testKeySecret);

    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: $transaction->transaction_number,
        payload: [
            'razorpay_order_id' => $serverOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $validSignature,
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeTrue()
        ->and($response->status)->toBe(PaymentStatus::PAID)
        ->and($response->gatewayTransactionId)->toBe($paymentId)
        ->and($response->failureCode)->toBeNull();
});

test('8. Invalid signature is strictly rejected', function () {
    $serverOrderId = 'order_rzp_valid_123';
    $paymentId = 'pay_rzp_valid_456';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-VERIFY-002',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    $tamperedSignature = 'tampered_signature_999999999999999999';

    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: $transaction->transaction_number,
        payload: [
            'razorpay_order_id' => $serverOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $tamperedSignature,
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeFalse()
        ->and($response->status)->toBe(PaymentStatus::FAILED)
        ->and($response->failureCode)->toBe('INVALID_SIGNATURE');
});

test('9. Missing payment ID is rejected with validation error', function () {
    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: 'TXN-MISSING-01',
        payload: [
            'razorpay_order_id' => 'order_123',
            'razorpay_signature' => 'sig_123',
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeFalse()
        ->and($response->failureCode)->toBe('MISSING_PAYMENT_DATA');
});

test('10. Missing Razorpay order ID is rejected', function () {
    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: 'TXN-MISSING-02',
        payload: [
            'razorpay_payment_id' => 'pay_123',
            'razorpay_signature' => 'sig_123',
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeFalse()
        ->and($response->failureCode)->toBe('MISSING_PAYMENT_DATA');
});

test('11. Missing signature is rejected', function () {
    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: 'TXN-MISSING-03',
        payload: [
            'razorpay_payment_id' => 'pay_123',
            'razorpay_order_id' => 'order_123',
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeFalse()
        ->and($response->failureCode)->toBe('MISSING_PAYMENT_DATA');
});

test('12. Signature verification uses the server-side stored Razorpay order ID, not client-provided order ID', function () {
    $serverOrderId = 'order_server_authoritative_123';
    $clientManipulatedOrderId = 'order_attacker_fake_456';
    $paymentId = 'pay_rzp_tamper_001';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-TAMPER-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    // Attacker generates signature using their client order ID
    $attackerSignature = hash_hmac('sha256', $clientManipulatedOrderId.'|'.$paymentId, $this->testKeySecret);

    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: $transaction->transaction_number,
        payload: [
            'razorpay_order_id' => $clientManipulatedOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $attackerSignature,
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeFalse()
        ->and($response->failureCode)->toBe('ORDER_ID_MISMATCH');
});

test('13. Client-provided order ID cannot be used to bypass verification', function () {
    $serverOrderId = 'order_real_server_999';
    $paymentId = 'pay_real_999';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-BYPASS-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    // Signature generated against completely different order ID
    $wrongSignature = hash_hmac('sha256', 'order_random_bypass|'.$paymentId, $this->testKeySecret);

    $gateway = new RazorpayPaymentGateway(null, $this->testKeyId, $this->testKeySecret);

    $verificationRequest = new PaymentVerificationRequest(
        transactionNumber: $transaction->transaction_number,
        payload: [
            'razorpay_order_id' => $serverOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $wrongSignature,
        ]
    );

    $response = $gateway->verifyPayment($verificationRequest);

    expect($response->success)->toBeFalse()
        ->and($response->failureCode)->toBe('INVALID_SIGNATURE');
});

/*
|--------------------------------------------------------------------------
| 3. PaymentService & Order Lifecycle Integration Tests
|--------------------------------------------------------------------------
*/

test('14. Successful verification marks payment PAID through existing PaymentService and transitions order to PROCESSING', function () {
    $serverOrderId = 'order_full_flow_111';
    $paymentId = 'pay_full_flow_222';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-FLOW-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    $validSignature = hash_hmac('sha256', $serverOrderId.'|'.$paymentId, $this->testKeySecret);

    $response = $this->paymentService->processPaymentVerification(
        transaction: $transaction,
        payload: [
            'razorpay_order_id' => $serverOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $validSignature,
        ]
    );

    expect($response->success)->toBeTrue()
        ->and($response->status)->toBe(PaymentStatus::PAID);

    // Assert database updates
    $updatedTxn = $transaction->fresh();
    expect($updatedTxn->status)->toBe(PaymentStatus::PAID)
        ->and($updatedTxn->gateway_transaction_id)->toBe($paymentId)
        ->and($updatedTxn->paid_at)->not->toBeNull();

    $updatedOrder = $this->order->fresh();
    expect($updatedOrder->payment_status)->toBe(PaymentStatus::PAID)
        ->and($updatedOrder->status)->toBe(OrderStatus::PROCESSING);
});

test('15. Failed payment does not mark order PAID', function () {
    $serverOrderId = 'order_fail_flow_333';
    $paymentId = 'pay_fail_flow_444';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-FAIL-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    $invalidSignature = 'bad_sig_fail_check';

    $response = $this->paymentService->processPaymentVerification(
        transaction: $transaction,
        payload: [
            'razorpay_order_id' => $serverOrderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $invalidSignature,
        ]
    );

    expect($response->success)->toBeFalse()
        ->and($response->status)->toBe(PaymentStatus::FAILED);

    $updatedTxn = $transaction->fresh();
    expect($updatedTxn->status)->toBe(PaymentStatus::FAILED);

    $updatedOrder = $this->order->fresh();
    expect($updatedOrder->payment_status)->toBe(PaymentStatus::FAILED)
        ->and($updatedOrder->status)->toBe(OrderStatus::PENDING); // Order status does not become PROCESSING
});

test('16. Duplicate verification is strictly idempotent', function () {
    $serverOrderId = 'order_idem_555';
    $paymentId = 'pay_idem_666';

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-IDEM-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => $serverOrderId,
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
        'payload' => ['gateway_reference' => $serverOrderId],
    ]);

    $validSignature = hash_hmac('sha256', $serverOrderId.'|'.$paymentId, $this->testKeySecret);

    $payload = [
        'razorpay_order_id' => $serverOrderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $validSignature,
    ];

    // First verification
    $firstRes = $this->paymentService->processPaymentVerification($transaction, $payload);
    expect($firstRes->success)->toBeTrue()
        ->and($firstRes->status)->toBe(PaymentStatus::PAID);

    // Second replay verification
    $secondRes = $this->paymentService->processPaymentVerification($transaction, $payload);
    expect($secondRes->success)->toBeTrue()
        ->and($secondRes->status)->toBe(PaymentStatus::PAID);

    // Must still have exactly 1 payment transaction
    expect(PaymentTransaction::where('transaction_number', 'TXN-IDEM-001')->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| 4. Security, Tampering & IDOR Protection Tests
|--------------------------------------------------------------------------
*/

test('17. Customer cannot verify another customer payment (strict IDOR)', function () {
    $otherCustomer = Customer::factory()->create(['is_active' => true]);

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id, // belongs to $this->customer
        'transaction_number' => 'TXN-IDOR-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => 'order_idor_123',
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
    ]);

    $this->actingAs($otherCustomer, 'customer');
    session(['customer_auth_token_version' => $otherCustomer->auth_token_version]);

    $response = $this->post(route('checkout.payment.verify'), [
        'transaction_number' => 'TXN-IDOR-001',
        'razorpay_payment_id' => 'pay_idor_123',
        'razorpay_order_id' => 'order_idor_123',
        'razorpay_signature' => 'dummy_sig',
    ]);

    $response->assertStatus(404);
});

test('18. Amount tampering: frontend cannot alter the authoritative payment amount', function () {
    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();

    // Server must use order->grand_total (1575.00 -> 157500 paise) regardless of any client input
    $orderResource->shouldReceive('create')
        ->once()
        ->withArgs(fn ($payload) => $payload['amount'] === 157500)
        ->andReturn((object) ['id' => 'order_tamper_test_99']);
    $mockApi->order = $orderResource;

    $gateway = $this->gatewayManager->gateway('razorpay');
    $gateway->setApi($mockApi);

    // Client requests initiation through PaymentService
    $initiation = $this->paymentService->initiatePayment($this->order, 'razorpay');

    $txn = PaymentTransaction::where('transaction_number', $initiation->transactionNumber)->first();
    expect((float) $txn->amount)->toBe(1575.00);
});

test('19. Razorpay API errors are handled safely without exposing secrets', function () {
    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();
    $orderResource->shouldReceive('create')
        ->once()
        ->andThrow(new Exception('Unauthorized: Invalid API Key supplied.'));
    $mockApi->order = $orderResource;

    $gateway = new RazorpayPaymentGateway($mockApi, $this->testKeyId, $this->testKeySecret);

    $request = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'TXN-ERR-001',
        amount: '100.00',
        currency: 'INR',
        returnUrl: 'http://localhost/checkout/complete'
    );

    try {
        $gateway->initiatePayment($request);
        $this->fail('Expected PaymentGatewayException was not thrown.');
    } catch (PaymentGatewayException $e) {
        expect($e->getMessage())->toContain('Failed to create Razorpay order')
            ->and($e->getMessage())->not->toContain($this->testKeySecret);
    }
});

test('20. HTTP checkout payment page loads correctly with IDOR protection', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $mockApi = Mockery::mock(Api::class);
    $orderResource = Mockery::mock();
    $orderResource->shouldReceive('create')
        ->andReturn((object) ['id' => 'order_page_test_123']);
    $mockApi->order = $orderResource;
    $this->gatewayManager->gateway('razorpay')->setApi($mockApi);

    $response = $this->get(route('checkout.payment', $this->order->order_number));

    $response->assertStatus(200)
        ->assertSee('Complete Your Order Payment')
        ->assertSee('#'.$this->order->order_number)
        ->assertSee('1,575.00')
        ->assertSee($this->testKeyId)
        ->assertDontSee($this->testKeySecret);
});

test('21. HTTP checkout payment cancellation works and records cancelled state', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $transaction = PaymentTransaction::create([
        'order_id' => $this->order->id,
        'transaction_number' => 'TXN-CANCEL-001',
        'gateway' => 'razorpay',
        'gateway_transaction_id' => 'order_cancel_123',
        'amount' => '1575.00',
        'currency' => 'INR',
        'status' => PaymentStatus::PENDING,
    ]);

    $response = $this->post(route('checkout.payment.cancel'), [
        'transaction_number' => 'TXN-CANCEL-001',
        'reason' => 'Customer closed payment dialog',
    ]);

    $response->assertRedirect(route('account.orders.show', $this->order->order_number));
    expect($transaction->fresh()->status)->toBe(PaymentStatus::CANCELLED);
});

test('22. Existing NullPaymentGateway continues to resolve and work flawlessly', function () {
    $nullGateway = $this->gatewayManager->gateway('null');

    expect($nullGateway)->toBeInstanceOf(NullPaymentGateway::class)
        ->and($nullGateway->getIdentifier())->toBe('null');

    $request = new PaymentInitiationRequest(
        order: $this->order,
        transactionNumber: 'TXN-NULL-TEST',
        amount: '1575.00',
        currency: 'INR',
        returnUrl: 'http://localhost/checkout/complete'
    );

    $res = $nullGateway->initiatePayment($request);
    expect($res->success)->toBeTrue()
        ->and($res->gatewayReference)->toStartWith('NULL-REF-');
});
