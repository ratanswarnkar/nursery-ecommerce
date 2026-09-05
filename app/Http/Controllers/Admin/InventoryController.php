<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryAdjustRequest;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $query = Inventory::with([
            'productVariant.product',
            'productVariant.attributeValues.attribute',
            'warehouse',
        ]);

        if ($search = $request->input('search')) {
            $query->whereHas('productVariant', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('(quantity - reserved_quantity) <= safety_stock');
        }

        $inventories = $query->orderBy('warehouse_id')
            ->orderBy('product_variant_id')
            ->paginate(20)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code', 'is_active']);
        $movementTypes = StockMovementType::cases();

        return view('admin.inventory.index', compact('inventories', 'warehouses', 'movementTypes'));
    }

    public function adjust(InventoryAdjustRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $admin = auth('admin')->user();
        $type = ! empty($data['type']) ? StockMovementType::from($data['type']) : StockMovementType::ADJUSTMENT;

        if ($data['adjustment_mode'] === 'set') {
            $this->inventoryService->setStock(
                (int) $data['product_variant_id'],
                (int) $data['warehouse_id'],
                (int) $data['quantity'],
                $data['notes'] ?? null,
                $admin
            );
        } else {
            $this->inventoryService->adjustStock(
                (int) $data['product_variant_id'],
                (int) $data['warehouse_id'],
                (int) $data['quantity'],
                $type,
                $data['notes'] ?? null,
                $admin
            );
        }

        if (isset($data['safety_stock']) && $data['safety_stock'] !== '') {
            $this->inventoryService->updateSafetyStock(
                (int) $data['product_variant_id'],
                (int) $data['warehouse_id'],
                (int) $data['safety_stock'],
                $admin
            );
        }

        return redirect()->back()
            ->with('success', 'Inventory stock adjusted successfully.');
    }

    public function movements(Request $request): View
    {
        $query = StockMovement::with([
            'productVariant.product',
            'warehouse',
        ]);

        if ($variantId = $request->input('product_variant_id')) {
            $query->where('product_variant_id', $variantId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $movements = $query->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.inventory.movements', compact('movements', 'warehouses'));
    }
}
