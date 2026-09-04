<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\TaxClass;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\CategoryHierarchyService;
use App\Services\Catalog\ProductManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductManagementService $productService,
        protected CategoryHierarchyService $hierarchyService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $query = Product::with([
            'brand:id,name',
            'categories:id,name,slug',
            'variants:id,product_id,sku,price,compare_at_price,is_active,is_default',
            'primaryImage',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('base_sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->has('is_featured') && $request->input('is_featured') !== '') {
            $query->where('is_featured', (bool) $request->input('is_featured'));
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        $brands = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categoriesTree = $this->hierarchyService->getTree();

        return view('admin.products.index', compact('products', 'brands', 'categoriesTree'));
    }

    public function create(): View
    {
        $categoriesTree = $this->hierarchyService->getTree();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $taxClasses = TaxClass::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('categoriesTree', 'brands', 'taxClasses'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->productService->createProduct($request->validated(), auth('admin')->user());

        return redirect()->route('admin.products.show', $product)
            ->with('success', "Product '{$product->name}' created successfully with base variant.");
    }

    public function show(Product $product): View
    {
        $product->load([
            'brand',
            'taxClass',
            'categories',
            'variants.attributeValues.attribute',
            'variants.inventories',
            'images',
            'seoMetadata',
        ]);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load(['categories', 'seoMetadata']);
        $categoriesTree = $this->hierarchyService->getTree();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $taxClasses = TaxClass::where('is_active', true)->orderBy('name')->get();

        $selectedCategoryIds = $product->categories->pluck('id')->all();
        $primaryCategoryId = $product->categories->firstWhere('pivot.is_primary', true)?->id;

        return view('admin.products.edit', compact(
            'product',
            'categoriesTree',
            'brands',
            'taxClasses',
            'selectedCategoryIds',
            'primaryCategoryId'
        ));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->updateProduct($product, $request->validated(), auth('admin')->user());

        return redirect()->route('admin.products.show', $product)
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $this->productService->deleteProduct($product, auth('admin')->user());

        return redirect()->route('admin.products.index')
            ->with('success', "Product '{$name}' deleted successfully.");
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $this->auditLogger->logAdminEvent(
            'catalog.product_status_toggled',
            auth('admin')->user(),
            ['id' => $product->id, 'is_active' => $product->is_active],
            $product
        );

        $statusStr = $product->is_active ? 'published/active' : 'unpublished/inactive';

        return redirect()->back()
            ->with('success', "Product '{$product->name}' is now {$statusStr}.");
    }
}
