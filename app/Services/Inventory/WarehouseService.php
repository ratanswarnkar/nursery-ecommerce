<?php

namespace App\Services\Inventory;

use App\Models\Admin;
use App\Models\Warehouse;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create a new warehouse while enforcing the single active default invariant.
     */
    public function createWarehouse(array $data, ?Admin $admin = null): Warehouse
    {
        return DB::transaction(function () use ($data, $admin) {
            // Pessimistic lock on warehouses to prevent concurrent default conflicts
            Warehouse::lockForUpdate()->get();

            $totalCount = Warehouse::count();
            $shouldBeDefault = ! empty($data['is_default']) || $totalCount === 0;
            $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;

            if ($shouldBeDefault) {
                // Ensure default warehouse is active
                $isActive = true;
                Warehouse::query()->update(['is_default' => false]);
            }

            $warehouse = Warehouse::create([
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'])),
                'address_line_1' => $data['address_line_1'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'India',
                'is_active' => $isActive,
                'is_default' => $shouldBeDefault,
            ]);

            $this->auditLogger->logAdminEvent(
                'warehouse.created',
                $admin,
                ['id' => $warehouse->id, 'code' => $warehouse->code, 'is_default' => $warehouse->is_default],
                $warehouse
            );

            return $warehouse;
        });
    }

    /**
     * Update an existing warehouse respecting the single active default invariant.
     */
    public function updateWarehouse(Warehouse $warehouse, array $data, ?Admin $admin = null): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data, $admin) {
            Warehouse::lockForUpdate()->get();

            $wasDefault = (bool) $warehouse->is_default;
            $willBeDefault = isset($data['is_default']) ? (bool) $data['is_default'] : $wasDefault;
            $willBeActive = isset($data['is_active']) ? (bool) $data['is_active'] : $warehouse->is_active;

            // Invariant: cannot directly unset is_default on the default warehouse without designating another
            if ($wasDefault && ! $willBeDefault) {
                throw ValidationException::withMessages([
                    'is_default' => ['A default warehouse is required. Set another warehouse as default instead.'],
                ]);
            }

            // Invariant: deactivating the default warehouse requires promoting another active warehouse
            if ($wasDefault && ! $willBeActive) {
                $otherActive = Warehouse::where('id', '!=', $warehouse->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();

                if (! $otherActive) {
                    throw ValidationException::withMessages([
                        'is_active' => ['Cannot deactivate the default warehouse when no other active warehouse exists.'],
                    ]);
                }

                // Promote other active warehouse to default
                $otherActive->update(['is_default' => true]);
                $willBeDefault = false;
            }

            // If becoming default, unset all others and force active
            if ($willBeDefault && ! $wasDefault) {
                Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
                $willBeActive = true;
            }

            $warehouse->update([
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'])),
                'address_line_1' => $data['address_line_1'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'India',
                'is_active' => $willBeActive,
                'is_default' => $willBeDefault,
            ]);

            $this->auditLogger->logAdminEvent(
                'warehouse.updated',
                $admin,
                ['id' => $warehouse->id, 'changes' => $warehouse->getChanges()],
                $warehouse
            );

            return $warehouse;
        });
    }

    /**
     * Delete a warehouse safely.
     */
    public function deleteWarehouse(Warehouse $warehouse, ?Admin $admin = null): void
    {
        DB::transaction(function () use ($warehouse, $admin) {
            Warehouse::lockForUpdate()->get();

            // 1. Check positive inventory
            $hasStock = $warehouse->inventories()->where('quantity', '>', 0)->exists();
            if ($hasStock) {
                throw ValidationException::withMessages([
                    'warehouse' => ['Cannot delete warehouse with on-hand inventory. Please transfer or adjust physical stock to zero first.'],
                ]);
            }

            $totalWarehouses = Warehouse::count();
            if ($totalWarehouses <= 1) {
                throw ValidationException::withMessages([
                    'warehouse' => ['Cannot delete the only registered warehouse in the system.'],
                ]);
            }

            // 2. If deleting the default warehouse, promote another active warehouse
            if ($warehouse->is_default) {
                $nextDefault = Warehouse::where('id', '!=', $warehouse->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first()
                    ?? Warehouse::where('id', '!=', $warehouse->id)
                        ->orderBy('id')
                        ->first();

                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true, 'is_active' => true]);
                }
            }

            $id = $warehouse->id;
            $code = $warehouse->code;

            $warehouse->delete();

            $this->auditLogger->logAdminEvent(
                'warehouse.deleted',
                $admin,
                ['id' => $id, 'code' => $code],
                $warehouse
            );
        });
    }
}
