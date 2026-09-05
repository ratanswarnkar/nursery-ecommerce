<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WarehouseRequest;
use App\Models\Warehouse;
use App\Services\Inventory\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    public function index(Request $request): View
    {
        $warehouses = Warehouse::withCount('inventories')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('admin.warehouses.create');
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        $warehouse = $this->warehouseService->createWarehouse($request->validated(), auth('admin')->user());

        return redirect()->route('admin.warehouses.index')
            ->with('success', "Warehouse '{$warehouse->name}' ({$warehouse->code}) created successfully.");
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('admin.warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->warehouseService->updateWarehouse($warehouse, $request->validated(), auth('admin')->user());

        return redirect()->route('admin.warehouses.index')
            ->with('success', "Warehouse '{$warehouse->name}' updated successfully.");
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        try {
            $this->warehouseService->deleteWarehouse($warehouse, auth('admin')->user());

            return redirect()->route('admin.warehouses.index')
                ->with('success', "Warehouse '{$warehouse->name}' deleted successfully.");
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? $e->getMessage();

            return redirect()->route('admin.warehouses.index')
                ->with('error', $message);
        }
    }

    public function toggleStatus(Warehouse $warehouse): RedirectResponse
    {
        $this->warehouseService->updateWarehouse($warehouse, [
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'is_active' => ! $warehouse->is_active,
        ], auth('admin')->user());

        $statusStr = $warehouse->fresh()->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Warehouse '{$warehouse->name}' is now {$statusStr}.");
    }
}
