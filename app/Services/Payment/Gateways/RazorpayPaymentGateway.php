<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentStatus;
use App\Exceptions\Payment\PaymentGatewayException;
use App\Models\PaymentTransaction;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentInitiationRequest;
use App\Services\Payment\DTO\PaymentInitiationResponse;
use App\Services\Payment\DTO\PaymentStatusResponse;
use App\Services\Payment\DTO\PaymentVerificationRequest;
use App\Services\Payment\DTO\PaymentVerificationResponse;
use Razorpay\Api\Api;
use Throwable;

class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected ?Api $api = null,
        protected ?string $keyId = null,
        protected ?string $keySecret = null
    ) {
        $this->keyId = $keyId ?: (string) config('services.razorpay.key_id', '');
        $this->keySecret = $keySecret ?: (string) config('services.razorpay.key_secret', '');
    }

    /**
     * Get or initialize the Razorpay API SDK instance.
     *
     * @throws PaymentGatewayException
     */
    public function getApi(): Api
    {
        if ($this->api === null) {
            if (empty($this->keyId) || empty($this->keySecret)) {
                throw new PaymentGatewayException(
                    message: 'Razorpay API credentials (key_id and key_secret) are not configured.',
                    gateway: 'razorpay'
                );
            }

            $this->api = new Api($this->keyId, $this->keySecret);
        }

        return $this->api;
    }

    /**
     * Set a custom or mock Razorpay API SDK instance (useful for automated testing).
     */
    public function setApi(Api $api): self
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Get the configured public Key ID (safe to expose to browser).
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Initiate a payment session and create a Razorpay Order via Razorpay Orders API.
     *
     * @throws PaymentGatewayException
     */
    public function initiatePayment(PaymentInitiationRequest $request): PaymentInitiationResponse
    {
        $amountFloat = (float) $request->amount;
        if ($amountFloat <= 0) {
            throw new PaymentGatewayException(
                message: 'Payment amount must be positive.',
                gateway: 'razorpay'
            );
        }

        // Convert authoritative decimal amount to integer paise (e.g., INR 1575.00 -> 157500 paise)
        $amountInPaise = (int) round($amountFloat * 100);
        $currency = $request->currency ?: 'INR';

        try {
            $api = $this->getApi();

            $orderPayload = [
                'receipt' => $request->transactionNumber,
                'amount' => $amountInPaise,
                'currency' => $currency,
                'notes' => [
                    'order_number' => $request->order->order_number,
                    'transaction_number' => $request->transactionNumber,
                ],
            ];

            $razorpayOrder = $api->order->create($orderPayload);

            $razorpayOrderId = is_array($razorpayOrder)
                ? (string) ($razorpayOrder['id'] ?? '')
                : (string) ($razorpayOrder->id ?? '');

            if (empty($razorpayOrderId)) {
                throw new PaymentGatewayException(
                    message: 'Razorpay API did not return a valid order ID.',
                    gateway: 'razorpay'
                );
            }

            return new PaymentInitiationResponse(
                success: true,
                transactionNumber: $request->transactionNumber,
                redirectUrl: null,
                gatewayReference: $razorpayOrderId,
                errorMessage: null,
                rawPayload: [
                    'gateway' => 'razorpay',
                    'razorpay_order_id' => $razorpayOrderId,
                    'amount_in_paise' => $amountInPaise,
                    'currency' => $currency,
                    'key_id' => $this->keyId,
                ]
            );
        } catch (PaymentGatewayException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new PaymentGatewayException(
                message: 'Failed to create Razorpay order: '.$e->getMessage(),
                gateway: 'razorpay',
                previous: $e
            );
        }
    }

    /**
     * Verify payment signature server-side using HMAC-SHA256 with authoritative server-side order ID.
     */
    public function verifyPayment(PaymentVerificationRequest $request): PaymentVerificationResponse
    {
        $payload = $request->payload;

        $razorpayPaymentId = $payload['razorpay_payment_id'] ?? null;
        $razorpaySignature = $payload['razorpay_signature'] ?? null;
        $clientOrderId = $payload['razorpay_order_id'] ?? null;

        if (empty($razorpayPaymentId) || empty($razorpaySignature) || empty($clientOrderId)) {
            return new PaymentVerificationResponse(
                success: false,
                status: PaymentStatus::FAILED,
                gatewayTransactionId: $razorpayPaymentId,
                failureCode: 'MISSING_PAYMENT_DATA',
                failureMessage: 'Payment verification parameters (payment_id, order_id, signature) are missing.',
                rawPayload: [
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_order_id' => $clientOrderId,
                ]
            );
        }

        // Retrieve authoritative server-side Razorpay order ID from PaymentTransaction
        $transaction = PaymentTransaction::where('transaction_number', $request->transactionNumber)->first();
        $serverSideOrderId = $transaction?->gateway_transaction_id
            ?? $transaction?->payload['gateway_reference']
            ?? $transaction?->payload['razorpay_order_id']
            ?? null;

        if (empty($serverSideOrderId)) {
            return new PaymentVerificationResponse(
                success: false,
                status: PaymentStatus::FAILED,
                gatewayTransactionId: $razorpayPaymentId,
                failureCode: 'SERVER_ORDER_NOT_FOUND',
                failureMessage: 'No server-side Razorpay order found for this transaction.',
                rawPayload: [
                    'razorpay_payment_id' => $razorpayPaymentId,
                ]
            );
        }

        // Validate that client-provided order ID matches server-side order ID
        if ($clientOrderId !== $serverSideOrderId) {
            return new PaymentVerificationResponse(
                success: false,
                status: PaymentStatus::FAILED,
                gatewayTransactionId: $razorpayPaymentId,
                failureCode: 'ORDER_ID_MISMATCH',
                failureMessage: 'Client-provided order ID does not match the server-side order ID.',
                rawPayload: [
                    'client_order_id' => $clientOrderId,
                    'razorpay_payment_id' => $razorpayPaymentId,
                ]
            );
        }

        // Compute HMAC-SHA256 signature using the authoritative server-side order ID and secret
        $dataToSign = $serverSideOrderId.'|'.$razorpayPaymentId;
        $expectedSignature = hash_hmac('sha256', $dataToSign, $this->keySecret);

        if (! hash_equals($expectedSignature, $razorpaySignature)) {
            return new PaymentVerificationResponse(
                success: false,
                status: PaymentStatus::FAILED,
                gatewayTransactionId: $razorpayPaymentId,
                failureCode: 'INVALID_SIGNATURE',
                failureMessage: 'Razorpay payment signature verification failed.',
                rawPayload: [
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'server_order_id' => $serverSideOrderId,
                ]
            );
        }

        // Verification succeeded! Return PAID response with authoritative transaction amount
        return new PaymentVerificationResponse(
            success: true,
            status: PaymentStatus::PAID,
            gatewayTransactionId: $razorpayPaymentId,
            amount: $transaction ? (string) $transaction->amount : null,
            currency: $transaction->currency ?? 'INR',
            paymentMethod: $payload['payment_method'] ?? 'razorpay',
            failureCode: null,
            failureMessage: null,
            rawPayload: [
                'gateway' => 'razorpay',
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $serverSideOrderId,
                'verified_at' => now()->toIso8601String(),
            ]
        );
    }

    /**
     * Query simulated/cached payment status.
     */
    public function getPaymentStatus(string $transactionNumber): PaymentStatusResponse
    {
        $transaction = PaymentTransaction::where('transaction_number', $transactionNumber)->first();
        if (! $transaction) {
            return new PaymentStatusResponse(
                status: PaymentStatus::PENDING,
                gatewayTransactionId: null,
                amount: null,
                rawPayload: ['error' => 'Transaction not found']
            );
        }

        return new PaymentStatusResponse(
            status: $transaction->status,
            gatewayTransactionId: $transaction->gateway_transaction_id,
            amount: (string) $transaction->amount,
            rawPayload: ['gateway' => 'razorpay', 'status' => $transaction->status->value]
        );
    }

    /**
     * Return unique gateway identifier.
     */
    public function getIdentifier(): string
    {
        return 'razorpay';
    }
}
