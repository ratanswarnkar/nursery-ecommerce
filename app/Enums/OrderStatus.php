<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';
    case REFUNDED = 'refunded';

    /**
     * Determine if a transition to the target status is allowed.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true; // Idempotent same-state check
        }

        return match ($this) {
            self::PENDING => in_array($target, [
                self::CONFIRMED,
                self::PROCESSING,
                self::CANCELLED,
            ], true),

            self::CONFIRMED => in_array($target, [
                self::PROCESSING,
                self::CANCELLED,
            ], true),

            self::PROCESSING => in_array($target, [
                self::SHIPPED,
                self::CANCELLED,
            ], true),

            self::SHIPPED => in_array($target, [
                self::OUT_FOR_DELIVERY,
                self::DELIVERED,
            ], true),

            self::OUT_FOR_DELIVERY => in_array($target, [
                self::DELIVERED,
            ], true),

            // DELIVERED is a terminal fulfillment state in Phase 6.3.
            // DELIVERED -> RETURNED is strictly disallowed.
            self::DELIVERED,
            self::CANCELLED,
            self::RETURNED,
            self::REFUNDED => false,
        };
    }

    /**
     * Determine if the current order status is terminal.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::DELIVERED,
            self::CANCELLED,
            self::RETURNED,
            self::REFUNDED,
        ], true);
    }

    /**
     * Return list of permitted target statuses from current status.
     *
     * @return array<self>
     */
    public function availableTransitions(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $candidate) => $candidate !== $this && $this->canTransitionTo($candidate)
        ));
    }
}
