<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTO\PaymentInitiationRequest;
use App\Services\Payment\DTO\PaymentInitiationResponse;
use App\Services\Payment\DTO\PaymentStatusResponse;
use App\Services\Payment\DTO\PaymentVerificationRequest;
use App\Services\Payment\DTO\PaymentVerificationResponse;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment session with the payment gateway.
     */
    public function initiatePayment(PaymentInitiationRequest $request): PaymentInitiationResponse;

    /**
     * Verify payment authenticity, status, and payload from the gateway callback/webhook.
     */
    public function verifyPayment(PaymentVerificationRequest $request): PaymentVerificationResponse;

    /**
     * Query the payment status directly from the gateway.
     */
    public function getPaymentStatus(string $transactionNumber): PaymentStatusResponse;

    /**
     * Gateway identifier (e.g. 'null').
     */
    public function getIdentifier(): string;
}
