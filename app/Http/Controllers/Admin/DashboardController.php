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
use App\Models\Shipment;
use App\Models\Tender;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the operational admin dashboard with live counters and recent activity.
     */
    public function index(): View
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $totalCategories = Category::count();
        $totalBrands = Brand::count();
        $lowStockCount = Inventory::whereColumn('quantity', '<=', 'safety_stock')
            ->distinct('product_variant_id')
            ->count('product_variant_id');

        $metrics = [
            'total_orders' => Order::count(),
            'paid_orders' => Order::where('payment_status', PaymentStatus::PAID)->count(),
            'pending_orders' => Order::where('status', OrderStatus::PENDING)->count(),
            'total_revenue' => (float) PaymentTransaction::where('status', PaymentStatus::PAID)->sum('amount'),

            'total_customers' => Customer::count(),
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'total_categories' => $totalCategories,
            'total_brands' => $totalBrands,
            'low_stock_count' => $lowStockCount,

            'active_tenders' => Tender::where('status', TenderStatus::ACTIVE)->count(),
            'pending_returns' => OrderReturn::where('status', ReturnStatus::REQUESTED)->count(),
            'in_transit_shipments' => Shipment::where('shipping_status', ShippingStatus::PARTIALLY_FULFILLED)->count(),
        ];

        $recentOrders = Order::with('customer')->latest()->limit(5)->get();

        $recentActivity = AuditLog::latest()
            ->limit(6)
            ->with(['admin:id,name', 'customer:id,name'])
            ->get();

        return view('admin.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'totalCategories',
            'totalBrands',
            'lowStockCount',
            'metrics',
            'recentOrders',
            'recentActivity'
        ));
    }
}
