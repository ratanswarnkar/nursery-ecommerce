<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';

    /**
     * Check if a transition from the current status to the target status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true; // Idempotent same-state transition
        }

        return match ($this) {
            self::PENDING => in_array($target, [
                self::AUTHORIZED,
                self::PAID,
                self::FAILED,
                self::CANCELLED,
                self::EXPIRED,
            ], true),

            self::AUTHORIZED => in_array($target, [
                self::PAID,
                self::FAILED,
                self::CANCELLED,
                self::EXPIRED,
            ], true),

            self::PAID => in_array($target, [
                self::REFUNDED,
            ], true),

            self::FAILED => in_array($target, [
                self::PENDING, // Allows starting a fresh payment attempt
            ], true),

            self::CANCELLED,
            self::EXPIRED,
            self::REFUNDED => false, // Terminal states
        };
    }

    /**
     * Determine if this payment state is terminal.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::REFUNDED, self::CANCELLED, self::EXPIRED], true);
    }
}
