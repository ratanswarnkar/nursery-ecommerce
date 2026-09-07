<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Exceptions\Order\InvalidOrderStatusTransitionException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderStatusHistory;
use App\Services\Audit\AuditLogger;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class OrderLifecycleService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Transition an order to a new status with transactional locking, state validation,
     * status history audit trail, and inventory release on cancellation.
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function transitionStatus(
        Order $order,
        OrderStatus $targetStatus,
        ?string $comment = null,
        ?Admin $admin = null
    ): Order {
        return DB::transaction(function () use ($order, $targetStatus, $comment, $admin) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $fromStatus = $lockedOrder->status;

            // Idempotent no-op: If already in target status, return cleanly without duplicate side-effects
            if ($fromStatus === $targetStatus) {
                return $lockedOrder;
            }

            // State machine transition validation
            if (! $fromStatus->canTransitionTo($targetStatus)) {
                throw new InvalidOrderStatusTransitionException($fromStatus, $targetStatus);
            }

            // If transitioning to CANCELLED, safely release physical inventory
            if ($targetStatus === OrderStatus::CANCELLED) {
                $this->inventoryService->releaseStockForOrder($lockedOrder, $admin);

                OrderCancellation::updateOrCreate(
                    ['order_id' => $lockedOrder->id],
                    [
                        'reason' => $comment ?: 'Order cancelled by administrator.',
                        'status' => 'approved',
                        'requested_by_type' => $admin ? Admin::class : null,
                        'requested_by_id' => $admin?->id,
                        'approved_by_id' => $admin?->id,
                        'approved_at' => now(),
                    ]
                );
            }

            // Update order status (PaymentStatus remains decoupled and untouched)
            $lockedOrder->update(['status' => $targetStatus]);

            // Append to immutable order status history
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'comment' => $comment ?: "Order status changed from '{$fromStatus->value}' to '{$targetStatus->value}'.",
                'changed_by_type' => $admin ? Admin::class : null,
                'changed_by_id' => $admin?->id,
            ]);

            // Audit admin event
            $this->auditLogger->logAdminEvent(
                'order.status_updated',
                $admin,
                [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'from_status' => $fromStatus->value,
                    'to_status' => $targetStatus->value,
                    'comment' => $comment,
                ],
                $lockedOrder
            );

            return $lockedOrder;
        });
    }

    /**
     * Cancel an order with required reason.
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function cancelOrder(Order $order, string $reason, ?Admin $admin = null): Order
    {
        return $this->transitionStatus(
            order: $order,
            targetStatus: OrderStatus::CANCELLED,
            comment: $reason,
            admin: $admin
        );
    }
}
