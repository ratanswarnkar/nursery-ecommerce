<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnStatus;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Services\Audit\AuditLogger;
use App\Services\Inventory\InventoryService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OrderReturnService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected PaymentService $paymentService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Determine if an order is eligible for return submission.
     */
    public function canRequestReturn(Order $order): bool
    {
        // 1. Must be physically delivered
        if ($order->status !== OrderStatus::DELIVERED) {
            return false;
        }

        // 2. Must be paid or partially refunded
        if (! in_array($order->payment_status, [PaymentStatus::PAID, PaymentStatus::PARTIALLY_REFUNDED], true)) {
            return false;
        }

        // 3. Must not have an active pending return request awaiting admin review
        $hasPendingReturn = OrderReturn::where('order_id', $order->id)
            ->where('status', ReturnStatus::REQUESTED)
            ->exists();

        if ($hasPendingReturn) {
            return false;
        }

        // 4. Must have at least one item with remaining returnable quantity > 0
        return $this->getReturnableItems($order)->isNotEmpty();
    }

    /**
     * Calculate remaining returnable quantity for a specific order item.
     * Purchased quantity MINUS already requested/approved/completed returns.
     */
    public function getReturnableQuantity(OrderItem $item): int
    {
        $claimedQuantity = (int) OrderReturn::where('order_item_id', $item->id)
            ->whereIn('status', [
                ReturnStatus::REQUESTED,
                ReturnStatus::APPROVED,
                ReturnStatus::COMPLETED,
            ])
            ->sum('quantity');

        return max(0, (int) $item->quantity - $claimedQuantity);
    }

    /**
     * Get all items in the order that still have returnable quantity > 0.
     *
     * @return Collection<int, OrderItem>
     */
    public function getReturnableItems(Order $order): Collection
    {
        $items = $order->items()->get();

        return $items->filter(function (OrderItem $item) {
            $item->returnable_quantity = $this->getReturnableQuantity($item);

            return $item->returnable_quantity > 0;
        })->values();
    }

    /**
     * Create a customer return request with transactional pessimistic locking,
     * strict quantity validation, duplicate prevention, and audit history.
     *
     * @param  array<int, array{order_item_id: int, quantity: int}>  $itemsData
     * @return array<OrderReturn>
     *
     * @throws ValidationException
     */
    public function createReturnRequest(
        Order $order,
        array $itemsData,
        string $reason,
        Customer $customer
    ): array {
        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => 'A reason for the return request is required.',
            ]);
        }

        if (empty($itemsData)) {
            throw ValidationException::withMessages([
                'items' => 'At least one item must be selected for return.',
            ]);
        }

        return DB::transaction(function () use ($order, $itemsData, $reason, $customer) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Authorization check
            if ($lockedOrder->customer_id !== $customer->id) {
                throw ValidationException::withMessages([
                    'order' => 'Unauthorized order return request.',
                ]);
            }

            // Status check
            if ($lockedOrder->status !== OrderStatus::DELIVERED) {
                throw ValidationException::withMessages([
                    'order' => 'Only delivered orders are eligible for return.',
                ]);
            }

            // Payment check
            if (! in_array($lockedOrder->payment_status, [PaymentStatus::PAID, PaymentStatus::PARTIALLY_REFUNDED], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Only paid orders are eligible for return/refund.',
                ]);
            }

            // Prevent duplicate active return requests on this order
            $activeRequestsCount = OrderReturn::where('order_id', $lockedOrder->id)
                ->where('status', ReturnStatus::REQUESTED)
                ->lockForUpdate()
                ->count();

            if ($activeRequestsCount > 0) {
                throw ValidationException::withMessages([
                    'order' => 'An active return request is already pending review for this order.',
                ]);
            }

            $orderItemIds = collect($itemsData)->pluck('order_item_id')->all();
            $orderItems = OrderItem::where('order_id', $lockedOrder->id)
                ->whereIn('id', $orderItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $createdReturns = [];
            $summaryLines = [];

            foreach ($itemsData as $entry) {
                $itemId = (int) ($entry['order_item_id'] ?? 0);
                $quantity = (int) ($entry['quantity'] ?? 0);

                /** @var OrderItem|null $item */
                $item = $orderItems->get($itemId);

                if (! $item) {
                    throw ValidationException::withMessages([
                        'items' => "Invalid order item ID #{$itemId}.",
                    ]);
                }

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}" => "Invalid return quantity for {$item->product_name}.",
                    ]);
                }

                $availableQuantity = $this->getReturnableQuantity($item);

                if ($quantity > $availableQuantity) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}" => "Requested quantity ({$quantity}) exceeds returnable quantity ({$availableQuantity}) for {$item->product_name}.",
                    ]);
                }

                // Server-calculated refund amount (never trust client input)
                $unitPrice = $item->quantity > 0 ? ((float) $item->total / (float) $item->quantity) : 0.0;
                $refundAmount = round($unitPrice * $quantity, 2);

                $orderReturn = OrderReturn::create([
                    'order_id' => $lockedOrder->id,
                    'order_item_id' => $item->id,
                    'quantity' => $quantity,
                    'reason' => trim($reason),
                    'status' => ReturnStatus::REQUESTED,
                    'refund_amount' => $refundAmount,
                ]);

                $createdReturns[] = $orderReturn;
                $summaryLines[] = "{$item->product_name} (Qty: {$quantity}, Est. Refund: ₹".number_format($refundAmount, 2).')';
            }

            // Immutable Order Status History
            $historyComment = 'Return requested by customer. Reason: '.trim($reason).'. Items: '.implode('; ', $summaryLines);
            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $lockedOrder->status,
                'to_status' => $lockedOrder->status,
                'comment' => $historyComment,
                'changed_by_type' => Customer::class,
                'changed_by_id' => $customer->id,
            ]);

            // Audit Trail
            $this->auditLogger->logCustomerEvent(
                'order.return_requested',
                $customer,
                [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'return_ids' => collect($createdReturns)->pluck('id')->all(),
                    'reason' => $reason,
                ],
                $lockedOrder
            );

            return $createdReturns;
        });
    }

    /**
     * Admin approves a return request.
     */
    public function approveReturn(OrderReturn $orderReturn, ?string $adminNotes, Admin $admin): OrderReturn
    {
        return DB::transaction(function () use ($orderReturn, $adminNotes, $admin) {
            /** @var OrderReturn $lockedReturn */
            $lockedReturn = OrderReturn::where('id', $orderReturn->id)->lockForUpdate()->firstOrFail();

            if ($lockedReturn->status !== ReturnStatus::REQUESTED) {
                throw new InvalidArgumentException("Return #{$lockedReturn->id} cannot be approved from status '{$lockedReturn->status->value}'.");
            }

            $lockedReturn->update([
                'status' => ReturnStatus::APPROVED,
                'admin_notes' => $adminNotes ? trim($adminNotes) : $lockedReturn->admin_notes,
                'approved_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedReturn->order_id,
                'from_status' => $lockedReturn->order->status,
                'to_status' => $lockedReturn->order->status,
                'comment' => "Return #{$lockedReturn->id} approved by admin. ".($adminNotes ? "Notes: {$adminNotes}" : ''),
                'changed_by_type' => Admin::class,
                'changed_by_id' => $admin->id,
            ]);

            $this->auditLogger->logAdminEvent(
                'order.return_approved',
                $admin,
                [
                    'order_return_id' => $lockedReturn->id,
                    'order_id' => $lockedReturn->order_id,
                    'admin_notes' => $adminNotes,
                ],
                $lockedReturn
            );

            return $lockedReturn;
        });
    }

    /**
     * Admin rejects a return request with mandatory reason.
     */
    public function rejectReturn(OrderReturn $orderReturn, string $reason, Admin $admin): OrderReturn
    {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException('A reason is required to reject a return request.');
        }

        return DB::transaction(function () use ($orderReturn, $reason, $admin) {
            /** @var OrderReturn $lockedReturn */
            $lockedReturn = OrderReturn::where('id', $orderReturn->id)->lockForUpdate()->firstOrFail();

            if ($lockedReturn->status !== ReturnStatus::REQUESTED) {
                throw new InvalidArgumentException("Return #{$lockedReturn->id} cannot be rejected from status '{$lockedReturn->status->value}'.");
            }

            $lockedReturn->update([
                'status' => ReturnStatus::REJECTED,
                'admin_notes' => trim($reason),
                'rejected_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedReturn->order_id,
                'from_status' => $lockedReturn->order->status,
                'to_status' => $lockedReturn->order->status,
                'comment' => "Return #{$lockedReturn->id} rejected by admin. Reason: ".trim($reason),
                'changed_by_type' => Admin::class,
                'changed_by_id' => $admin->id,
            ]);

            $this->auditLogger->logAdminEvent(
                'order.return_rejected',
                $admin,
                [
                    'order_return_id' => $lockedReturn->id,
                    'order_id' => $lockedReturn->order_id,
                    'reason' => $reason,
                ],
                $lockedReturn
            );

            return $lockedReturn;
        });
    }

    /**
     * Mark physical return completed when received.
     * Optionally restocks returned inventory if requested by admin.
     */
    public function completeReturn(OrderReturn $orderReturn, Admin $admin, bool $restock = false): OrderReturn
    {
        return DB::transaction(function () use ($orderReturn, $admin, $restock) {
            /** @var OrderReturn $lockedReturn */
            $lockedReturn = OrderReturn::where('id', $orderReturn->id)->lockForUpdate()->firstOrFail();

            if ($lockedReturn->status !== ReturnStatus::APPROVED) {
                throw new InvalidArgumentException("Only approved returns can be completed. Return #{$lockedReturn->id} is in '{$lockedReturn->status->value}'.");
            }

            $lockedReturn->update([
                'status' => ReturnStatus::COMPLETED,
                'completed_at' => now(),
                'received_at' => $lockedReturn->received_at ?? now(),
            ]);

            if ($restock) {
                $this->inventoryService->restockReturnedItems($lockedReturn, $admin);
            }

            OrderStatusHistory::create([
                'order_id' => $lockedReturn->order_id,
                'from_status' => $lockedReturn->order->status,
                'to_status' => $lockedReturn->order->status,
                'comment' => "Return #{$lockedReturn->id} marked completed/received by admin.".($restock ? ' Stock restocked.' : ''),
                'changed_by_type' => Admin::class,
                'changed_by_id' => $admin->id,
            ]);

            $this->auditLogger->logAdminEvent(
                'order.return_completed',
                $admin,
                [
                    'order_return_id' => $lockedReturn->id,
                    'order_id' => $lockedReturn->order_id,
                    'restocked' => $restock,
                ],
                $lockedReturn
            );

            return $lockedReturn;
        });
    }

    /**
     * Explicit administrative action to restock returned items for a completed return.
     */
    public function restockReturn(OrderReturn $orderReturn, Admin $admin): void
    {
        DB::transaction(function () use ($orderReturn, $admin) {
            /** @var OrderReturn $lockedReturn */
            $lockedReturn = OrderReturn::where('id', $orderReturn->id)->lockForUpdate()->firstOrFail();

            if ($lockedReturn->status !== ReturnStatus::COMPLETED) {
                throw new InvalidArgumentException("Items can only be restocked after physical return is completed. Return #{$lockedReturn->id} is '{$lockedReturn->status->value}'.");
            }

            $this->inventoryService->restockReturnedItems($lockedReturn, $admin);

            OrderStatusHistory::create([
                'order_id' => $lockedReturn->order_id,
                'from_status' => $lockedReturn->order->status,
                'to_status' => $lockedReturn->order->status,
                'comment' => "Restocked {$lockedReturn->quantity} unit(s) for return #{$lockedReturn->id}.",
                'changed_by_type' => Admin::class,
                'changed_by_id' => $admin->id,
            ]);

            $this->auditLogger->logAdminEvent(
                'order.return_restocked',
                $admin,
                [
                    'order_return_id' => $lockedReturn->id,
                    'order_id' => $lockedReturn->order_id,
                    'quantity' => $lockedReturn->quantity,
                ],
                $lockedReturn
            );
        });
    }
}
