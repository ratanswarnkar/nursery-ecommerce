<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnStatus;
use App\Enums\ShippingStatus;
use App\Enums\TenderStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tender;
use App\Models\Warehouse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the operational business analytics admin dashboard with real-data metrics and charts.
     */
    public function index(): View
    {
        // ----------------------------------------------------
        // 1. PRODUCT ANALYTICS
        // ----------------------------------------------------
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $totalCategories = Category::count();
        $totalBrands = Brand::count();

        // Products with zero available stock across active warehouses
        $outOfStockProducts = Product::where('is_active', true)
            ->whereDoesntHave('variants', function ($q) {
                $q->where('is_active', true)
                    ->whereHas('inventories', function ($iq) {
                        $iq->whereRaw('quantity > reserved_quantity')
                            ->whereHas('warehouse', fn ($wq) => $wq->where('is_active', true));
                    });
            })->count();

        // Products with active variants at or below safety stock threshold (and > 0)
        $lowStockProducts = Product::where('is_active', true)
            ->whereHas('variants', function ($q) {
                $q->where('is_active', true)
                    ->whereHas('inventories', function ($iq) {
                        $iq->whereRaw('quantity > reserved_quantity')
                            ->whereRaw('(quantity - reserved_quantity) <= safety_stock')
                            ->whereHas('warehouse', fn ($wq) => $wq->where('is_active', true));
                    });
            })->count();

        // Products by Category distribution
        $categoriesWithCounts = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderByDesc('products_count')
            ->get();

        // ----------------------------------------------------
        // 2. ORDER & SALES ANALYTICS
        // ----------------------------------------------------
        $totalOrders = Order::count();
        $orderStatusRaw = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $orderStatuses = [
            'pending' => $orderStatusRaw[OrderStatus::PENDING->value] ?? 0,
            'confirmed' => $orderStatusRaw[OrderStatus::CONFIRMED->value] ?? 0,
            'processing' => $orderStatusRaw[OrderStatus::PROCESSING->value] ?? 0,
            'shipped' => $orderStatusRaw[OrderStatus::SHIPPED->value] ?? 0,
            'out_for_delivery' => $orderStatusRaw[OrderStatus::OUT_FOR_DELIVERY->value] ?? 0,
            'delivered' => $orderStatusRaw[OrderStatus::DELIVERED->value] ?? 0,
            'cancelled' => $orderStatusRaw[OrderStatus::CANCELLED->value] ?? 0,
            'returned' => $orderStatusRaw[OrderStatus::RETURNED->value] ?? 0,
        ];

        // ----------------------------------------------------
        // 3. PAYMENT & REVENUE ANALYTICS
        // ----------------------------------------------------
        $paymentStatusRaw = PaymentTransaction::selectRaw('status, count(*) as count, coalesce(sum(amount), 0) as total')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($item) => $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status);

        $paidTx = $paymentStatusRaw->get(PaymentStatus::PAID->value);
        $pendingTx = $paymentStatusRaw->get(PaymentStatus::PENDING->value);
        $failedTx = $paymentStatusRaw->get(PaymentStatus::FAILED->value);
        $cancelledTx = $paymentStatusRaw->get(PaymentStatus::CANCELLED->value);
        $refundedTx = $paymentStatusRaw->get(PaymentStatus::REFUNDED->value);

        $paymentSummary = [
            'paid' => ['count' => $paidTx ? (int) $paidTx->count : 0, 'total' => $paidTx ? (float) $paidTx->total : 0.0],
            'pending' => ['count' => $pendingTx ? (int) $pendingTx->count : 0, 'total' => $pendingTx ? (float) $pendingTx->total : 0.0],
            'failed' => ['count' => $failedTx ? (int) $failedTx->count : 0, 'total' => $failedTx ? (float) $failedTx->total : 0.0],
            'cancelled' => ['count' => $cancelledTx ? (int) $cancelledTx->count : 0, 'total' => $cancelledTx ? (float) $cancelledTx->total : 0.0],
            'refunded' => ['count' => $refundedTx ? (int) $refundedTx->count : 0, 'total' => $refundedTx ? (float) $refundedTx->total : 0.0],
        ];

        $totalPaidRevenue = $paidTx ? (float) $paidTx->total : 0.0;
        $recentPaidRevenue = (float) PaymentTransaction::where('status', PaymentStatus::PAID)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        $paidOrdersCount = Order::where('payment_status', PaymentStatus::PAID)->count();
        $averageOrderValue = $paidOrdersCount > 0
            ? (float) Order::where('payment_status', PaymentStatus::PAID)->avg('grand_total')
            : 0.0;

        // ----------------------------------------------------
        // 4. INVENTORY ANALYTICS
        // ----------------------------------------------------
        $totalInventoryQty = (int) Inventory::sum('quantity');
        $totalReservedQty = (int) Inventory::sum('reserved_quantity');
        $inventoryInStockCount = Inventory::whereRaw('(quantity - reserved_quantity) > safety_stock')->count();
        $inventoryLowStockCount = Inventory::whereRaw('quantity > reserved_quantity AND (quantity - reserved_quantity) <= safety_stock')->count();
        $inventoryOutOfStockCount = Inventory::whereRaw('quantity <= reserved_quantity')->count();

        $warehouses = Warehouse::withCount('inventories')
            ->withSum('inventories', 'quantity')
            ->get();

        $stockMovementsCount = StockMovement::count();
        $recentMovementsCount = StockMovement::where('created_at', '>=', now()->subDays(30))->count();

        // ----------------------------------------------------
        // 5. SHIPPING / FULFILLMENT ANALYTICS
        // ----------------------------------------------------
        $shippingStatusRaw = Order::selectRaw('shipping_status, count(*) as count')
            ->groupBy('shipping_status')
            ->pluck('count', 'shipping_status')
            ->all();

        $shippingStatuses = [
            'unfulfilled' => $shippingStatusRaw[ShippingStatus::UNFULFILLED->value] ?? 0,
            'partially_fulfilled' => $shippingStatusRaw[ShippingStatus::PARTIALLY_FULFILLED->value] ?? 0,
            'fulfilled' => $shippingStatusRaw[ShippingStatus::FULFILLED->value] ?? 0,
            'returned' => $shippingStatusRaw[ShippingStatus::RETURNED->value] ?? 0,
        ];

        // ----------------------------------------------------
        // 6. RETURNS / REFUNDS ANALYTICS
        // ----------------------------------------------------
        $returnStatusRaw = OrderReturn::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $returnStatuses = [
            'requested' => $returnStatusRaw[ReturnStatus::REQUESTED->value] ?? 0,
            'approved' => $returnStatusRaw[ReturnStatus::APPROVED->value] ?? 0,
            'rejected' => $returnStatusRaw[ReturnStatus::REJECTED->value] ?? 0,
            'completed' => $returnStatusRaw[ReturnStatus::COMPLETED->value] ?? 0,
        ];
        $totalRefundsValue = (float) PaymentTransaction::where('status', PaymentStatus::REFUNDED)->sum('amount');

        // ----------------------------------------------------
        // 7. TENDER ANALYTICS
        // ----------------------------------------------------
        $totalTenders = Tender::count();
        $tenderStatusRaw = Tender::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $tenderStatuses = [
            'draft' => $tenderStatusRaw[TenderStatus::DRAFT->value] ?? 0,
            'active' => $tenderStatusRaw[TenderStatus::ACTIVE->value] ?? 0,
            'completed' => $tenderStatusRaw[TenderStatus::COMPLETED->value] ?? 0,
            'cancelled' => $tenderStatusRaw[TenderStatus::CANCELLED->value] ?? 0,
            'archived' => $tenderStatusRaw[TenderStatus::ARCHIVED->value] ?? 0,
        ];

        // ----------------------------------------------------
        // 8. CUSTOMERS & RECENT ACTIVITY
        // ----------------------------------------------------
        $totalCustomers = Customer::count();
        $recentOrders = Order::with('customer')->latest()->limit(5)->get();
        $recentActivity = AuditLog::latest()
            ->limit(6)
            ->with(['admin:id,name', 'customer:id,name'])
            ->get();

        $metrics = [
            'total_revenue' => $totalPaidRevenue,
            'recent_paid_revenue' => $recentPaidRevenue,
            'average_order_value' => $averageOrderValue,
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrdersCount,
            'pending_orders' => $orderStatuses['pending'],
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'low_stock_products' => $lowStockProducts,
            'total_categories' => $totalCategories,
            'total_brands' => $totalBrands,
            'total_inventory_qty' => $totalInventoryQty,
            'total_reserved_qty' => $totalReservedQty,
            'inventory_in_stock' => $inventoryInStockCount,
            'inventory_low_stock' => $inventoryLowStockCount,
            'inventory_out_of_stock' => $inventoryOutOfStockCount,
            'stock_movements_count' => $stockMovementsCount,
            'recent_movements_count' => $recentMovementsCount,
            'active_tenders' => $tenderStatuses['active'],
            'total_tenders' => $totalTenders,
            'pending_returns' => $returnStatuses['requested'],
            'total_refunds_value' => $totalRefundsValue,
            'in_transit_shipments' => $shippingStatuses['partially_fulfilled'],
        ];

        $lowStockCount = Inventory::whereColumn('quantity', '<=', 'safety_stock')
            ->distinct('product_variant_id')
            ->count('product_variant_id');

        return view('admin.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'totalCategories',
            'totalBrands',
            'lowStockCount',
            'metrics',
            'categoriesWithCounts',
            'orderStatuses',
            'paymentSummary',
            'warehouses',
            'shippingStatuses',
            'returnStatuses',
            'tenderStatuses',
            'recentOrders',
            'recentActivity'
        ));
    }
}
