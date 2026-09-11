<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\Payment\DuplicatePaymentException;
use App\Exceptions\Payment\InvalidPaymentTransitionException;
use App\Exceptions\Payment\PaymentAmountMismatchException;
use App\Exceptions\Payment\PaymentGatewayException;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Services\Audit\AuditLogger;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentInitiationRequest;
use App\Services\Payment\DTO\PaymentInitiationResponse;
use App\Services\Payment\DTO\PaymentRefundRequest;
use App\Services\Payment\DTO\PaymentVerificationRequest;
use App\Services\Payment\DTO\PaymentVerificationResponse;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentTransactionNumberGenerator $numberGenerator,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Initiate a payment session for an order.
     *
     * @param  array<string, mixed>  $metadata
     *
     * @throws DomainException
     * @throws DuplicatePaymentException
     * @throws InvalidPaymentTransitionException
     * @throws PaymentGatewayException
     */
    public function initiatePayment(
        Order $order,
        ?string $gatewayName = null,
        ?string $idempotencyKey = null,
        ?Customer $customer = null,
        array $metadata = []
    ): PaymentInitiationResponse {
        return DB::transaction(function () use ($order, $gatewayName, $idempotencyKey, $customer, $metadata) {
            // Lock order for validation
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Validate order payment eligibility
            if ($lockedOrder->payment_status === PaymentStatus::PAID) {
                throw new DomainException("Cannot initiate payment: Order [{$lockedOrder->order_number}] is already paid.");
            }

            if (in_array($lockedOrder->status, [OrderStatus::CANCELLED, OrderStatus::RETURNED, OrderStatus::REFUNDED], true)) {
                throw new DomainException("Cannot initiate payment: Order [{$lockedOrder->order_number}] is in a non-payable terminal state [{$lockedOrder->status->value}].");
            }

            // Check idempotency
            if ($idempotencyKey !== null) {
                $existing = PaymentTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    if ($existing->order_id !== $lockedOrder->id) {
                        throw new DuplicatePaymentException($idempotencyKey, "Idempotency key [{$idempotencyKey}] is already associated with another order.");
                    }

                    // If existing transaction is already pending or paid, return its initiation state
                    return new PaymentInitiationResponse(
                        success: true,
                        transactionNumber: $existing->transaction_number,
                        redirectUrl: $existing->payload['redirect_url'] ?? null,
                        gatewayReference: $existing->gateway_transaction_id,
                        errorMessage: null,
                        rawPayload: $existing->payload ?? []
                    );
                }
            }

            // Enforce server-side amount integrity
            $amount = number_format((float) $lockedOrder->grand_total, 2, '.', '');
            $currency = $lockedOrder->currency ?: (string) config('payment.currency', 'INR');
            $gatewayDriver = $gatewayName ?: $this->gatewayManager->getDefaultDriver();
            $transactionNumber = $this->numberGenerator->generate();

            // Create pending payment transaction record
            $transaction = PaymentTransaction::create([
                'order_id' => $lockedOrder->id,
                'transaction_number' => $transactionNumber,
                'gateway' => $gatewayDriver,
                'gateway_transaction_id' => null,
                'amount' => $amount,
                'currency' => $currency,
                'status' => PaymentStatus::PENDING,
                'payment_method' => null,
                'idempotency_key' => $idempotencyKey,
                'payload' => array_merge([
                    'initiated_at' => now()->toIso8601String(),
                ], $metadata),
            ]);

            // Resolve gateway and initiate session
            /** @var PaymentGatewayInterface $gateway */
            $gateway = $this->gatewayManager->gateway($gatewayDriver);
            $returnUrl = $metadata['return_url'] ?? url("/checkout/complete?txn={$transactionNumber}");

            $requestDto = new PaymentInitiationRequest(
                order: $lockedOrder,
                transactionNumber: $transactionNumber,
                amount: $amount,
                currency: $currency,
                returnUrl: $returnUrl,
                customer: $customer ?? $lockedOrder->customer,
                metadata: $metadata
            );

            $response = $gateway->initiatePayment($requestDto);

            // Update transaction record with gateway reference and redirect URL
            $payload = $transaction->payload ?? [];
            if ($response->redirectUrl) {
                $payload['redirect_url'] = $response->redirectUrl;
            }
            if ($response->gatewayReference) {
                $payload['gateway_reference'] = $response->gatewayReference;
            }
            $payload['gateway_response'] = $response->rawPayload;

            $transaction->update([
                'gateway_transaction_id' => $response->gatewayReference,
                'payload' => $payload,
            ]);

            // Audit payment initiation
            $actingCustomer = $customer ?? $lockedOrder->customer;
            if ($actingCustomer) {
                $this->auditLogger->logCustomerEvent('payment.initiated', $actingCustomer, [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'transaction_number' => $transactionNumber,
                    'gateway' => $gatewayDriver,
                    'amount' => $amount,
                    'currency' => $currency,
                ], $transaction);
            } else {
                $this->auditLogger->logSecurityEvent('payment.initiated', [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'transaction_number' => $transactionNumber,
                    'gateway' => $gatewayDriver,
                    'amount' => $amount,
                    'currency' => $currency,
                ], $transaction);
            }

            return $response;
        });
    }

    /**
     * Process payment verification callback/webhook with idempotent concurrency locking.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     *
     * @throws InvalidPaymentTransitionException
     * @throws PaymentAmountMismatchException
     * @throws PaymentGatewayException
     */
    public function processPaymentVerification(
        PaymentTransaction|string $transaction,
        array $payload = [],
        array $headers = []
    ): PaymentVerificationResponse {
        $mismatchException = null;

        $response = DB::transaction(function () use ($transaction, $payload, $headers, &$mismatchException) {
            // Lock transaction and order
            $transactionNumber = $transaction instanceof PaymentTransaction
                ? $transaction->transaction_number
                : $transaction;

            /** @var PaymentTransaction $lockedTxn */
            $lockedTxn = PaymentTransaction::where('transaction_number', $transactionNumber)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedTxn->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency check: if transaction is already paid, return successful response without re-processing
            if ($lockedTxn->status === PaymentStatus::PAID) {
                return new PaymentVerificationResponse(
                    success: true,
                    status: PaymentStatus::PAID,
                    gatewayTransactionId: $lockedTxn->gateway_transaction_id,
                    amount: (string) $lockedTxn->amount,
                    currency: $lockedTxn->currency,
                    paymentMethod: $lockedTxn->payment_method,
                    rawPayload: $lockedTxn->payload ?? []
                );
            }

            // Resolve gateway
            $gateway = $this->gatewayManager->gateway($lockedTxn->gateway);

            // Execute verification via gateway contract
            $verificationRequest = new PaymentVerificationRequest(
                transactionNumber: $lockedTxn->transaction_number,
                payload: $payload,
                headers: $headers
            );

            $verificationResponse = $gateway->verifyPayment($verificationRequest);

            // Amount validation: if gateway returns an explicit amount, strictly verify against expected order total
            if ($verificationResponse->amount !== null) {
                $expectedAmount = number_format((float) $lockedTxn->amount, 2, '.', '');
                $actualAmount = number_format((float) $verificationResponse->amount, 2, '.', '');

                if (bccomp($expectedAmount, $actualAmount, 2) !== 0) {
                    $lockedTxn->update([
                        'status' => PaymentStatus::FAILED,
                        'failed_at' => now(),
                        'failure_code' => 'AMOUNT_MISMATCH',
                        'failure_message' => "Expected amount [{$expectedAmount}] but gateway reported [{$actualAmount}].",
                    ]);

                    $lockedOrder->update(['payment_status' => PaymentStatus::FAILED]);

                    $this->auditLogger->logSecurityEvent('payment.amount_mismatch', [
                        'transaction_number' => $lockedTxn->transaction_number,
                        'expected' => $expectedAmount,
                        'received' => $actualAmount,
                    ], $lockedTxn);

                    $mismatchException = new PaymentAmountMismatchException($expectedAmount, $actualAmount);

                    return null;
                }
            }

            // Transition validation
            $targetStatus = $verificationResponse->status;
            if (! $lockedTxn->status->canTransitionTo($targetStatus)) {
                throw new InvalidPaymentTransitionException($lockedTxn->status, $targetStatus);
            }

            // Merge gateway payload with audit scrubbing
            $existingPayload = $lockedTxn->payload ?? [];
            $mergedPayload = array_merge($existingPayload, [
                'verification_response' => $this->auditLogger->scrubSensitiveData($verificationResponse->rawPayload),
                'verified_at' => now()->toIso8601String(),
            ]);

            // Apply status-specific lifecycle transitions
            if ($targetStatus === PaymentStatus::PAID) {
                $lockedTxn->update([
                    'status' => PaymentStatus::PAID,
                    'gateway_transaction_id' => $verificationResponse->gatewayTransactionId ?? $lockedTxn->gateway_transaction_id,
                    'payment_method' => $verificationResponse->paymentMethod ?? $lockedTxn->payment_method,
                    'paid_at' => now(),
                    'failure_code' => null,
                    'failure_message' => null,
                    'payload' => $mergedPayload,
                ]);

                $previousOrderStatus = $lockedOrder->status;

                // Move order to processing upon payment capture if not already paid
                if ($lockedOrder->payment_status !== PaymentStatus::PAID) {
                    $lockedOrder->update([
                        'payment_status' => PaymentStatus::PAID,
                        'status' => OrderStatus::PROCESSING,
                    ]);

                    // Record order status history only once
                    OrderStatusHistory::create([
                        'order_id' => $lockedOrder->id,
                        'from_status' => $previousOrderStatus,
                        'to_status' => OrderStatus::PROCESSING,
                        'comment' => "Payment verified successfully via gateway [{$lockedTxn->gateway}].",
                        'changed_by_type' => null,
                        'changed_by_id' => null,
                    ]);
                }

                // Audit log payment success
                $customer = $lockedOrder->customer;
                if ($customer) {
                    $this->auditLogger->logCustomerEvent('payment.paid', $customer, [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'transaction_number' => $lockedTxn->transaction_number,
                        'gateway_transaction_id' => $lockedTxn->gateway_transaction_id,
                        'amount' => (string) $lockedTxn->amount,
                    ], $lockedTxn);
                } else {
                    $this->auditLogger->logSecurityEvent('payment.paid', [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'transaction_number' => $lockedTxn->transaction_number,
                        'gateway_transaction_id' => $lockedTxn->gateway_transaction_id,
                        'amount' => (string) $lockedTxn->amount,
                    ], $lockedTxn);
                }
            } elseif ($targetStatus === PaymentStatus::FAILED) {
                $lockedTxn->update([
                    'status' => PaymentStatus::FAILED,
                    'gateway_transaction_id' => $verificationResponse->gatewayTransactionId ?? $lockedTxn->gateway_transaction_id,
                    'payment_method' => $verificationResponse->paymentMethod ?? $lockedTxn->payment_method,
                    'failed_at' => now(),
                    'failure_code' => $verificationResponse->failureCode ?? 'PAYMENT_FAILED',
                    'failure_message' => $verificationResponse->failureMessage ?? 'Payment failed.',
                    'payload' => $mergedPayload,
                ]);

                // Decoupled lifecycle: Update payment status only if order is not already PAID
                if ($lockedOrder->payment_status !== PaymentStatus::PAID) {
                    $lockedOrder->update([
                        'payment_status' => PaymentStatus::FAILED,
                    ]);
                }

                $customer = $lockedOrder->customer;
                if ($customer) {
                    $this->auditLogger->logCustomerEvent('payment.failed', $customer, [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'transaction_number' => $lockedTxn->transaction_number,
                        'failure_code' => $lockedTxn->failure_code,
                        'failure_message' => $lockedTxn->failure_message,
                    ], $lockedTxn);
                } else {
                    $this->auditLogger->logSecurityEvent('payment.failed', [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'transaction_number' => $lockedTxn->transaction_number,
                        'failure_code' => $lockedTxn->failure_code,
                        'failure_message' => $lockedTxn->failure_message,
                    ], $lockedTxn);
                }
            } elseif ($targetStatus === PaymentStatus::CANCELLED) {
                $lockedTxn->update([
                    'status' => PaymentStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'failure_code' => 'USER_CANCELLED',
                    'failure_message' => 'Payment cancelled by customer.',
                    'payload' => $mergedPayload,
                ]);

                // Only update order payment status if not already PAID
                if ($lockedOrder->payment_status !== PaymentStatus::PAID) {
                    $lockedOrder->update([
                        'payment_status' => PaymentStatus::CANCELLED,
                    ]);
                }

                $this->auditLogger->logSecurityEvent('payment.cancelled', [
                    'order_id' => $lockedOrder->id,
                    'transaction_number' => $lockedTxn->transaction_number,
                ], $lockedTxn);
            }

            return $verificationResponse;
        });

        if ($mismatchException !== null) {
            throw $mismatchException;
        }

        return $response;
    }

    /**
     * Cancel an existing pending payment transaction.
     *
     * @throws DomainException
     * @throws InvalidPaymentTransitionException
     */
    public function cancelPayment(PaymentTransaction|string $transaction, ?string $reason = null): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            $transactionNumber = $transaction instanceof PaymentTransaction
                ? $transaction->transaction_number
                : $transaction;

            /** @var PaymentTransaction $lockedTxn */
            $lockedTxn = PaymentTransaction::where('transaction_number', $transactionNumber)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedTxn->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: if already cancelled, return cleanly
            if ($lockedTxn->status === PaymentStatus::CANCELLED) {
                return $lockedTxn;
            }

            // Never cancel payment if order is already PAID
            if ($lockedOrder->payment_status === PaymentStatus::PAID) {
                throw new DomainException("Cannot cancel payment: Order [{$lockedOrder->order_number}] is already paid.");
            }

            if (! $lockedTxn->status->canTransitionTo(PaymentStatus::CANCELLED)) {
                throw new InvalidPaymentTransitionException($lockedTxn->status, PaymentStatus::CANCELLED);
            }

            $lockedTxn->update([
                'status' => PaymentStatus::CANCELLED,
                'cancelled_at' => now(),
                'failure_code' => 'CANCELLED',
                'failure_message' => $reason ?: 'Payment cancelled.',
            ]);

            if ($lockedOrder->payment_status !== PaymentStatus::PAID) {
                $lockedOrder->update([
                    'payment_status' => PaymentStatus::CANCELLED,
                ]);
            }

            $this->auditLogger->logSecurityEvent('payment.cancelled', [
                'transaction_number' => $lockedTxn->transaction_number,
                'order_id' => $lockedOrder->id,
                'reason' => $reason,
            ], $lockedTxn);

            return $lockedTxn;
        });
    }

    /**
     * Expire an existing pending payment transaction.
     *
     * @throws DomainException
     * @throws InvalidPaymentTransitionException
     */
    public function expirePayment(PaymentTransaction|string $transaction, ?string $reason = null): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            $transactionNumber = $transaction instanceof PaymentTransaction
                ? $transaction->transaction_number
                : $transaction;

            /** @var PaymentTransaction $lockedTxn */
            $lockedTxn = PaymentTransaction::where('transaction_number', $transactionNumber)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedTxn->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: if already expired, return cleanly
            if ($lockedTxn->status === PaymentStatus::EXPIRED) {
                return $lockedTxn;
            }

            // Never expire payment if order is already PAID
            if ($lockedOrder->payment_status === PaymentStatus::PAID) {
                throw new DomainException("Cannot expire payment: Order [{$lockedOrder->order_number}] is already paid.");
            }

            if (! $lockedTxn->status->canTransitionTo(PaymentStatus::EXPIRED)) {
                throw new InvalidPaymentTransitionException($lockedTxn->status, PaymentStatus::EXPIRED);
            }

            $lockedTxn->update([
                'status' => PaymentStatus::EXPIRED,
                'failure_code' => 'EXPIRED',
                'failure_message' => $reason ?: 'Payment expired.',
            ]);

            if ($lockedOrder->payment_status !== PaymentStatus::PAID) {
                $lockedOrder->update([
                    'payment_status' => PaymentStatus::EXPIRED,
                ]);
            }

            $this->auditLogger->logSecurityEvent('payment.expired', [
                'transaction_number' => $lockedTxn->transaction_number,
                'order_id' => $lockedOrder->id,
                'reason' => $reason,
            ], $lockedTxn);

            return $lockedTxn;
        });
    }

    /**
     * Process a full or partial payment refund with pessimistic locking,
     * remaining refundable balance validation, and idempotency protection.
     *
     * @throws DomainException
     * @throws PaymentGatewayException
     */
    public function processRefund(
        PaymentTransaction|string $transaction,
        float|string $amount,
        string $reason,
        ?OrderReturn $orderReturn = null,
        ?Admin $admin = null,
        ?string $idempotencyKey = null
    ): OrderRefund {
        return DB::transaction(function () use ($transaction, $amount, $reason, $orderReturn, $admin, $idempotencyKey) {
            $transactionNumber = $transaction instanceof PaymentTransaction
                ? $transaction->transaction_number
                : $transaction;

            /** @var PaymentTransaction $lockedTxn */
            $lockedTxn = PaymentTransaction::where('transaction_number', $transactionNumber)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedTxn->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Idempotency Check: if idempotency key exists, return the existing refund
            if ($idempotencyKey !== null) {
                $existingRefund = OrderRefund::where('idempotency_key', $idempotencyKey)->first();
                if ($existingRefund) {
                    return $existingRefund;
                }
            }

            // 2. Validate payment state: Only PAID or PARTIALLY_REFUNDED transactions can be refunded
            if (! in_array($lockedTxn->status, [PaymentStatus::PAID, PaymentStatus::PARTIALLY_REFUNDED], true)) {
                throw new DomainException("Transaction [{$lockedTxn->transaction_number}] cannot be refunded. Current payment status is [{$lockedTxn->status->value}].");
            }

            // 3. Calculate processed refunds & remaining refundable balance
            $processedRefunds = (float) $lockedTxn->refunds()->where('status', RefundStatus::PROCESSED)->sum('amount');
            $paidAmount = (float) $lockedTxn->amount;
            $remainingRefundable = round($paidAmount - $processedRefunds, 2);

            $refundAmount = round((float) $amount, 2);

            if ($refundAmount <= 0) {
                throw new DomainException('Refund amount must be greater than zero.');
            }

            if ($refundAmount > $remainingRefundable) {
                throw new DomainException("Refund amount [₹{$refundAmount}] exceeds remaining refundable balance [₹{$remainingRefundable}].");
            }

            // 4. Resolve gateway and call refund contract
            /** @var PaymentGatewayInterface $gateway */
            $gateway = $this->gatewayManager->gateway($lockedTxn->gateway);

            $refundRequest = new PaymentRefundRequest(
                order: $lockedOrder,
                transaction: $lockedTxn,
                gatewayPaymentId: (string) $lockedTxn->gateway_transaction_id,
                amount: number_format($refundAmount, 2, '.', ''),
                currency: $lockedTxn->currency ?: 'INR',
                reason: $reason,
                metadata: [
                    'order_return_id' => $orderReturn?->id,
                    'admin_id' => $admin?->id,
                ]
            );

            $response = $gateway->refundPayment($refundRequest);

            if (! $response->success) {
                // Record failed refund record for audit
                $failedRefund = OrderRefund::create([
                    'order_id' => $lockedOrder->id,
                    'order_return_id' => $orderReturn?->id,
                    'payment_transaction_id' => $lockedTxn->id,
                    'amount' => $refundAmount,
                    'reason' => $reason,
                    'status' => RefundStatus::FAILED,
                    'refund_reference' => null,
                    'idempotency_key' => $idempotencyKey,
                    'gateway' => $lockedTxn->gateway,
                    'gateway_refund_id' => null,
                    'payload' => $this->auditLogger->scrubSensitiveData($response->rawPayload),
                    'created_by_admin_id' => $admin?->id,
                ]);

                $this->auditLogger->logAdminEvent('payment.refund_failed', $admin, [
                    'transaction_number' => $lockedTxn->transaction_number,
                    'order_number' => $lockedOrder->order_number,
                    'amount' => $refundAmount,
                    'failure_code' => $response->failureCode,
                    'failure_message' => $response->failureMessage,
                ], $failedRefund);

                return $failedRefund;
            }

            // 5. Successful refund: Record OrderRefund
            $orderRefund = OrderRefund::create([
                'order_id' => $lockedOrder->id,
                'order_return_id' => $orderReturn?->id,
                'payment_transaction_id' => $lockedTxn->id,
                'amount' => $refundAmount,
                'reason' => $reason,
                'status' => RefundStatus::PROCESSED,
                'refund_reference' => $response->gatewayRefundId,
                'idempotency_key' => $idempotencyKey,
                'gateway' => $lockedTxn->gateway,
                'gateway_refund_id' => $response->gatewayRefundId,
                'payload' => $this->auditLogger->scrubSensitiveData($response->rawPayload),
                'created_by_admin_id' => $admin?->id,
                'processed_at' => now(),
            ]);

            // 6. Transition payment status
            $newTotalRefunded = round($processedRefunds + $refundAmount, 2);
            $targetPaymentStatus = ($newTotalRefunded >= $paidAmount)
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED;

            $lockedTxn->update(['status' => $targetPaymentStatus]);
            $lockedOrder->update(['payment_status' => $targetPaymentStatus]);

            // 7. Audit and status history
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $lockedOrder->status,
                'to_status' => $lockedOrder->status,
                'comment' => 'Refund of ₹'.number_format($refundAmount, 2)." processed successfully via [{$lockedTxn->gateway}] (Ref: {$response->gatewayRefundId}).",
                'changed_by_type' => $admin ? Admin::class : null,
                'changed_by_id' => $admin?->id,
            ]);

            $this->auditLogger->logAdminEvent('payment.refunded', $admin, [
                'transaction_number' => $lockedTxn->transaction_number,
                'order_number' => $lockedOrder->order_number,
                'amount' => $refundAmount,
                'total_refunded' => $newTotalRefunded,
                'gateway' => $lockedTxn->gateway,
                'gateway_refund_id' => $response->gatewayRefundId,
                'target_payment_status' => $targetPaymentStatus->value,
            ], $orderRefund);

            return $orderRefund;
        });
    }
}
