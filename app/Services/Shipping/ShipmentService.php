<?php

namespace App\Services\Shipping;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Exceptions\Order\InvalidOrderStatusTransitionException;
use App\Exceptions\Shipping\IneligibleForShipmentException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Services\Audit\AuditLogger;
use App\Services\Order\OrderLifecycleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(
        protected OrderLifecycleService $lifecycleService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create and dispatch a shipment for an eligible order.
     *
     * @throws IneligibleForShipmentException
     * @throws InvalidOrderStatusTransitionException
     */
    public function createShipment(
        Order $order,
        array $data,
        ?Admin $admin = null
    ): Shipment {
        return DB::transaction(function () use ($order, $data, $admin) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // 1. Re-check all eligibility conditions after acquiring pessimistic lock
            if ($lockedOrder->status->isTerminal()) {
                throw IneligibleForShipmentException::forOrder(
                    $lockedOrder->order_number,
                    "Cannot dispatch order in terminal status [{$lockedOrder->status->value}]."
                );
            }

            if ($lockedOrder->payment_status !== PaymentStatus::PAID) {
                throw IneligibleForShipmentException::forOrder(
                    $lockedOrder->order_number,
                    "Cannot dispatch unpaid order with payment status [{$lockedOrder->payment_status->value}]."
                );
            }

            if ($lockedOrder->status !== OrderStatus::PROCESSING) {
                throw IneligibleForShipmentException::forOrder(
                    $lockedOrder->order_number,
                    "Order must be in PROCESSING status to be dispatched. Current status: [{$lockedOrder->status->value}]."
                );
            }

            // 2. Prevent concurrent duplicate shipment creation
            $hasActiveShipment = $lockedOrder->shipments()
                ->whereIn('shipping_status', [ShippingStatus::FULFILLED, ShippingStatus::PARTIALLY_FULFILLED])
                ->exists();

            if ($hasActiveShipment || $lockedOrder->shipping_status === ShippingStatus::FULFILLED) {
                throw IneligibleForShipmentException::forOrder(
                    $lockedOrder->order_number,
                    'An active shipment has already been recorded for this order.'
                );
            }

            $lockedOrder->loadMissing('items');

            // 3. Construct historical items snapshot exclusively from OrderItem records
            $itemsSnapshot = $lockedOrder->items->map(function (OrderItem $item) {
                return [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => (int) $item->quantity,
                    'price' => (string) $item->price,
                    'subtotal' => (string) $item->subtotal,
                    'tax_amount' => (string) $item->tax_amount,
                    'discount_amount' => (string) $item->discount_amount,
                    'total' => (string) $item->total,
                ];
            })->values()->all();

            $estimatedDeliveryAt = ! empty($data['estimated_delivery_at'])
                ? Carbon::parse($data['estimated_delivery_at'])
                : null;

            // 4. Create Shipment
            $shipment = Shipment::create([
                'order_id' => $lockedOrder->id,
                'carrier' => ! empty($data['carrier']) ? trim($data['carrier']) : null,
                'tracking_number' => ! empty($data['tracking_number']) ? trim($data['tracking_number']) : null,
                'tracking_url' => ! empty($data['tracking_url']) ? trim($data['tracking_url']) : null,
                'shipping_status' => ShippingStatus::FULFILLED,
                'shipped_at' => Carbon::now(),
                'delivered_at' => null,
                'estimated_delivery_at' => $estimatedDeliveryAt,
                'notes' => ! empty($data['notes']) ? trim($data['notes']) : null,
                'items_snapshot' => $itemsSnapshot,
            ]);

            // 5. Transition order PROCESSING -> SHIPPED via OrderLifecycleService
            $carrierInfo = $shipment->carrier ?: 'courier';
            $trackingInfo = $shipment->tracking_number ? " (Tracking: {$shipment->tracking_number})" : '';
            $comment = "Order dispatched via {$carrierInfo}{$trackingInfo}.";

            $this->lifecycleService->transitionStatus(
                order: $lockedOrder,
                targetStatus: OrderStatus::SHIPPED,
                comment: $comment,
                admin: $admin
            );

            // 6. Synchronize order shipping_status
            $lockedOrder->update(['shipping_status' => ShippingStatus::FULFILLED]);

            // 7. Audit log the dispatch
            $this->auditLogger->logAdminEvent(
                'order.shipment_created',
                $admin,
                [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'shipment_id' => $shipment->id,
                    'carrier' => $shipment->carrier,
                    'tracking_number' => $shipment->tracking_number,
                    'tracking_url' => $shipment->tracking_url,
                    'estimated_delivery_at' => $shipment->estimated_delivery_at?->toIso8601String(),
                    'shipping_status' => $shipment->shipping_status->value,
                ],
                $shipment
            );

            return $shipment;
        });
    }

    /**
     * Mark an order shipment as out for delivery.
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function markOutForDelivery(
        Shipment $shipment,
        ?string $comment = null,
        ?Admin $admin = null
    ): Shipment {
        return DB::transaction(function () use ($shipment, $comment, $admin) {
            /** @var Shipment $lockedShipment */
            $lockedShipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedShipment->order_id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::SHIPPED) {
                throw new InvalidOrderStatusTransitionException($lockedOrder->status, OrderStatus::OUT_FOR_DELIVERY);
            }

            $transitionComment = $comment ?: 'Shipment is out for delivery with courier.';

            $this->lifecycleService->transitionStatus(
                order: $lockedOrder,
                targetStatus: OrderStatus::OUT_FOR_DELIVERY,
                comment: $transitionComment,
                admin: $admin
            );

            $this->auditLogger->logAdminEvent(
                'order.shipment_out_for_delivery',
                $admin,
                [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'shipment_id' => $lockedShipment->id,
                    'comment' => $transitionComment,
                ],
                $lockedShipment
            );

            return $lockedShipment;
        });
    }

    /**
     * Mark an order shipment as delivered.
     *
     * @throws InvalidOrderStatusTransitionException
     */
    public function markDelivered(
        Shipment $shipment,
        ?string $comment = null,
        ?Admin $admin = null
    ): Shipment {
        return DB::transaction(function () use ($shipment, $comment, $admin) {
            /** @var Shipment $lockedShipment */
            $lockedShipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedShipment->order_id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->status->canTransitionTo(OrderStatus::DELIVERED)) {
                throw new InvalidOrderStatusTransitionException($lockedOrder->status, OrderStatus::DELIVERED);
            }

            $transitionComment = $comment ?: 'Shipment delivered to customer.';

            $this->lifecycleService->transitionStatus(
                order: $lockedOrder,
                targetStatus: OrderStatus::DELIVERED,
                comment: $transitionComment,
                admin: $admin
            );

            $deliveredAt = Carbon::now();
            $lockedShipment->update([
                'delivered_at' => $deliveredAt,
                'shipping_status' => ShippingStatus::FULFILLED,
            ]);

            $lockedOrder->update([
                'shipping_status' => ShippingStatus::FULFILLED,
            ]);

            $this->auditLogger->logAdminEvent(
                'order.shipment_delivered',
                $admin,
                [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'shipment_id' => $lockedShipment->id,
                    'delivered_at' => $deliveredAt->toIso8601String(),
                    'comment' => $transitionComment,
                ],
                $lockedShipment
            );

            return $lockedShipment;
        });
    }

    /**
     * Update tracking and carrier information for an existing shipment.
     * Strictly restricts updates to mutable tracking fields only.
     */
    public function updateTrackingInformation(
        Shipment $shipment,
        array $data,
        ?Admin $admin = null
    ): Shipment {
        return DB::transaction(function () use ($shipment, $data, $admin) {
            /** @var Shipment $lockedShipment */
            $lockedShipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();

            $oldValues = [
                'carrier' => $lockedShipment->carrier,
                'tracking_number' => $lockedShipment->tracking_number,
                'tracking_url' => $lockedShipment->tracking_url,
                'estimated_delivery_at' => $lockedShipment->estimated_delivery_at?->toIso8601String(),
                'notes' => $lockedShipment->notes,
            ];

            $carrier = array_key_exists('carrier', $data)
                ? (filled($data['carrier']) ? trim($data['carrier']) : null)
                : $lockedShipment->carrier;

            $trackingNumber = array_key_exists('tracking_number', $data)
                ? (filled($data['tracking_number']) ? trim($data['tracking_number']) : null)
                : $lockedShipment->tracking_number;

            $trackingUrl = array_key_exists('tracking_url', $data)
                ? (filled($data['tracking_url']) ? trim($data['tracking_url']) : null)
                : $lockedShipment->tracking_url;

            $estimatedDeliveryAt = array_key_exists('estimated_delivery_at', $data)
                ? (filled($data['estimated_delivery_at']) ? Carbon::parse($data['estimated_delivery_at']) : null)
                : $lockedShipment->estimated_delivery_at;

            $notes = array_key_exists('notes', $data)
                ? (filled($data['notes']) ? trim($data['notes']) : null)
                : $lockedShipment->notes;

            $lockedShipment->update([
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingUrl,
                'estimated_delivery_at' => $estimatedDeliveryAt,
                'notes' => $notes,
            ]);

            $this->auditLogger->logAdminEvent(
                'order.shipment_tracking_updated',
                $admin,
                [
                    'order_id' => $lockedShipment->order_id,
                    'shipment_id' => $lockedShipment->id,
                    'old' => $oldValues,
                    'new' => [
                        'carrier' => $lockedShipment->carrier,
                        'tracking_number' => $lockedShipment->tracking_number,
                        'tracking_url' => $lockedShipment->tracking_url,
                        'estimated_delivery_at' => $lockedShipment->estimated_delivery_at?->toIso8601String(),
                        'notes' => $lockedShipment->notes,
                    ],
                ],
                $lockedShipment
            );

            return $lockedShipment;
        });
    }
}
