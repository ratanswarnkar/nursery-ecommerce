<?php

namespace App\Services\Payment\Gateways;

use App\Enums\PaymentStatus;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTO\PaymentInitiationRequest;
use App\Services\Payment\DTO\PaymentInitiationResponse;
use App\Services\Payment\DTO\PaymentStatusResponse;
use App\Services\Payment\DTO\PaymentVerificationRequest;
use App\Services\Payment\DTO\PaymentVerificationResponse;
use Illuminate\Support\Str;

class NullPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Initiate a simulated payment.
     */
    public function initiatePayment(PaymentInitiationRequest $request): PaymentInitiationResponse
    {
        $reference = 'NULL-REF-'.strtoupper(Str::random(12));

        return new PaymentInitiationResponse(
            success: true,
            transactionNumber: $request->transactionNumber,
            redirectUrl: $request->returnUrl.'?gateway=null&reference='.$reference,
            gatewayReference: $reference,
            errorMessage: null,
            rawPayload: [
                'gateway' => 'null',
                'reference' => $reference,
                'amount' => $request->amount,
                'currency' => $request->currency,
            ]
        );
    }

    /**
     * Verify payment based on provided payload parameters.
     */
    public function verifyPayment(PaymentVerificationRequest $request): PaymentVerificationResponse
    {
        $payload = $request->payload;
        $simulatedStatus = $payload['simulated_status'] ?? 'paid';

        if ($simulatedStatus === 'failed') {
            return new PaymentVerificationResponse(
                success: false,
                status: PaymentStatus::FAILED,
                gatewayTransactionId: $payload['gateway_transaction_id'] ?? ('NULL-FAIL-'.strtoupper(Str::random(10))),
                amount: $payload['amount'] ?? null,
                currency: $payload['currency'] ?? 'INR',
                paymentMethod: $payload['payment_method'] ?? 'simulated_null',
                failureCode: $payload['failure_code'] ?? 'SIMULATED_FAILURE',
                failureMessage: $payload['failure_message'] ?? 'Simulated payment failure via NullPaymentGateway.',
                rawPayload: $payload
            );
        }

        if ($simulatedStatus === 'cancelled') {
            return new PaymentVerificationResponse(
                success: false,
                status: PaymentStatus::CANCELLED,
                gatewayTransactionId: $payload['gateway_transaction_id'] ?? ('NULL-CANCEL-'.strtoupper(Str::random(10))),
                amount: $payload['amount'] ?? null,
                currency: $payload['currency'] ?? 'INR',
                paymentMethod: $payload['payment_method'] ?? 'simulated_null',
                failureCode: 'USER_CANCELLED',
                failureMessage: 'Payment was cancelled by the user.',
                rawPayload: $payload
            );
        }

        return new PaymentVerificationResponse(
            success: true,
            status: PaymentStatus::PAID,
            gatewayTransactionId: $payload['gateway_transaction_id'] ?? ('NULL-TXN-'.strtoupper(Str::random(12))),
            amount: $payload['amount'] ?? null,
            currency: $payload['currency'] ?? 'INR',
            paymentMethod: $payload['payment_method'] ?? 'simulated_null',
            failureCode: null,
            failureMessage: null,
            rawPayload: $payload
        );
    }

    /**
     * Query simulated payment status.
     */
    public function getPaymentStatus(string $transactionNumber): PaymentStatusResponse
    {
        return new PaymentStatusResponse(
            status: PaymentStatus::PENDING,
            gatewayTransactionId: 'NULL-STATUS-'.strtoupper(Str::random(10)),
            amount: null,
            rawPayload: ['gateway' => 'null', 'transaction_number' => $transactionNumber]
        );
    }

    /**
     * Return gateway identifier.
     */
    public function getIdentifier(): string
    {
        return 'null';
    }
}
