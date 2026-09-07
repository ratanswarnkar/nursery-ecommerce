<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Admin;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Adjust physical stock by a signed delta with pessimistic locking and append-only ledger creation.
     */
    public function adjustStock(
        int $variantId,
        int $warehouseId,
        int $delta,
        StockMovementType $type = StockMovementType::ADJUSTMENT,
        ?string $notes = null,
        ?Admin $admin = null
    ): Inventory {
        return DB::transaction(function () use ($variantId, $warehouseId, $delta, $type, $notes, $admin) {
            $variant = ProductVariant::findOrFail($variantId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            // Pessimistic lock on the inventory row
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = Inventory::create([
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'safety_stock' => 0,
                ]);

                // Re-lock the newly created record
                $inventory = Inventory::where('id', $inventory->id)->lockForUpdate()->first();
            }

            $previousQuantity = $inventory->quantity;
            $newQuantity = $previousQuantity + $delta;

            // Invariant enforcement: quantity >= reserved_quantity >= 0
            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ["Stock adjustment would cause negative on-hand quantity ({$newQuantity}). Current on-hand: {$previousQuantity}."],
                ]);
            }

            if ($newQuantity < $inventory->reserved_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Cannot reduce stock below committed reserved quantity ({$inventory->reserved_quantity}). Available to deduct: {$inventory->available_quantity}."],
                ]);
            }

            // Update physical inventory
            $inventory->update(['quantity' => $newQuantity]);

            // Append to immutable stock movement ledger
            StockMovement::create([
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => $delta,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'notes' => $notes,
                'created_by' => $admin?->id,
            ]);

            // Log admin audit event
            $this->auditLogger->logAdminEvent(
                'inventory.stock_adjusted',
                $admin,
                [
                    'variant_id' => $variantId,
                    'sku' => $variant->sku,
                    'warehouse_id' => $warehouseId,
                    'delta' => $delta,
                    'previous' => $previousQuantity,
                    'new' => $newQuantity,
                    'type' => $type->value,
                ],
                $inventory
            );

            return $inventory;
        });
    }

    /**
     * Set physical stock to an exact count (cycle-count adjustment).
     */
    public function setStock(
        int $variantId,
        int $warehouseId,
        int $targetQuantity,
        ?string $notes = null,
        ?Admin $admin = null
    ): Inventory {
        if ($targetQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Target stock quantity cannot be negative.'],
            ]);
        }

        return DB::transaction(function () use ($variantId, $warehouseId, $targetQuantity, $notes, $admin) {
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $currentQuantity = $inventory ? $inventory->quantity : 0;
            $delta = $targetQuantity - $currentQuantity;

            return $this->adjustStock(
                $variantId,
                $warehouseId,
                $delta,
                StockMovementType::ADJUSTMENT,
                $notes ?: "Cycle count set to {$targetQuantity}",
                $admin
            );
        });
    }

    /**
     * Update safety stock threshold for low-stock alerting.
     */
    public function updateSafetyStock(
        int $variantId,
        int $warehouseId,
        int $safetyStock,
        ?Admin $admin = null
    ): Inventory {
        if ($safetyStock < 0) {
            throw ValidationException::withMessages([
                'safety_stock' => ['Safety stock threshold cannot be negative.'],
            ]);
        }

        return DB::transaction(function () use ($variantId, $warehouseId, $safetyStock, $admin) {
            $inventory = Inventory::firstOrCreate(
                ['product_variant_id' => $variantId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'safety_stock' => 0]
            );

            $inventory->update(['safety_stock' => $safetyStock]);

            $this->auditLogger->logAdminEvent(
                'inventory.safety_stock_updated',
                $admin,
                ['variant_id' => $variantId, 'warehouse_id' => $warehouseId, 'safety_stock' => $safetyStock],
                $inventory
            );

            return $inventory;
        });
    }

    /**
     * Calculate total sellable stock across all active warehouses for a variant.
     */
    public function getAvailableStock(int $variantId): int
    {
        return (int) Inventory::where('product_variant_id', $variantId)
            ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
            ->sum(DB::raw('GREATEST(0, quantity - reserved_quantity)'));
    }

    /**
     * Check if a variant is at or below safety stock threshold across warehouses.
     */
    public function isLowStock(int $variantId): bool
    {
        $aggregates = Inventory::where('product_variant_id', $variantId)
            ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
            ->selectRaw('SUM(GREATEST(0, quantity - reserved_quantity)) as total_available, SUM(safety_stock) as total_safety')
            ->first();

        if (! $aggregates) {
            return true;
        }

        return (int) $aggregates->total_available <= (int) $aggregates->total_safety;
    }

    /**
     * Atomically deduct stock across active warehouses for an order with pessimistic locking and outbound ledger records.
     */
    public function deductStockForOrder(ProductVariant $variant, int $quantity, Order $order): void
    {
        if ($quantity <= 0) {
            return;
        }

        // Lock all inventory rows for this variant in active warehouses
        $inventories = Inventory::where('product_variant_id', $variant->id)
            ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
            ->lockForUpdate()
            ->with('warehouse')
            ->get();

        $totalAvailable = $inventories->sum(fn ($inv) => max(0, $inv->quantity - $inv->reserved_quantity));

        if ($totalAvailable < $quantity) {
            throw ValidationException::withMessages([
                'stock' => ["Insufficient stock for '{$variant->product?->name}' ({$variant->sku}). Requested: {$quantity}, Available: {$totalAvailable}."],
            ]);
        }

        // Allocate deduction across active warehouses (prioritize default warehouse, then largest available)
        $sortedInventories = $inventories->sortBy([
            fn ($a, $b) => $b->warehouse->is_default <=> $a->warehouse->is_default,
            fn ($a, $b) => ($b->quantity - $b->reserved_quantity) <=> ($a->quantity - $a->reserved_quantity),
        ]);

        $remainingToDeduct = $quantity;

        foreach ($sortedInventories as $inventory) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $availableInWarehouse = max(0, $inventory->quantity - $inventory->reserved_quantity);
            if ($availableInWarehouse <= 0) {
                continue;
            }

            $deductAmount = min($remainingToDeduct, $availableInWarehouse);
            $previousQuantity = $inventory->quantity;
            $newQuantity = $previousQuantity - $deductAmount;

            $inventory->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'product_variant_id' => $variant->id,
                'warehouse_id' => $inventory->warehouse_id,
                'type' => StockMovementType::OUTBOUND,
                'quantity' => -$deductAmount,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'notes' => "Order #{$order->order_number} fulfillment deduction",
                'created_by' => null,
            ]);

            $remainingToDeduct -= $deductAmount;
        }

        if ($remainingToDeduct > 0) {
            throw ValidationException::withMessages([
                'stock' => ["Failed to allocate required stock for '{$variant->sku}'."],
            ]);
        }
    }
}
