<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $totalCategories = Category::count();
        $totalBrands = Brand::count();
        $lowStockCount = Inventory::whereColumn('quantity', '<=', 'safety_stock')
            ->distinct('product_variant_id')
            ->count('product_variant_id');

        $recentActivity = AuditLog::where('action', 'like', 'catalog.%')
            ->latest()
            ->limit(5)
            ->with('admin:id,name')
            ->get();

        return view('admin.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'totalCategories',
            'totalBrands',
            'lowStockCount',
            'recentActivity'
        ));
    }
}
