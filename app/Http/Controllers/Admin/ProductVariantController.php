<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\ProductVariantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function __construct(
        protected ProductVariantService $variantService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Product $product): View
    {
        $variants = $product->variants()
            ->with(['attributeValues.attribute', 'inventories'])
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return view('admin.products.variants.index', compact('product', 'variants'));
    }

    public function create(Product $product): View
    {
        $attributes = Attribute::whereIn('type', [AttributeType::SELECT, AttributeType::MULTISELECT])
            ->with('values')
            ->orderBy('sort_order')
            ->get();

        return view('admin.products.variants.create', compact('product', 'attributes'));
    }

    public function store(ProductVariantRequest $request, Product $product): RedirectResponse
    {
        $variant = $this->variantService->createVariant($product, $request->validated());

        $this->auditLogger->logAdminEvent(
            'catalog.variant_created',
            auth('admin')->user(),
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'sku' => $variant->sku],
            $variant
        );

        return redirect()->route('admin.products.variants.index', $product)
            ->with('success', "Variant '{$variant->sku}' created successfully.");
    }

    public function edit(Product $product, ProductVariant $variant): View
    {
        abort_unless($variant->product_id === $product->id, 404);

        $variant->load('attributeValues');
        $selectedAttributeValueIds = $variant->attributeValues->pluck('id')->all();

        $attributes = Attribute::whereIn('type', [AttributeType::SELECT, AttributeType::MULTISELECT])
            ->with('values')
            ->orderBy('sort_order')
            ->get();

        return view('admin.products.variants.edit', compact('product', 'variant', 'attributes', 'selectedAttributeValueIds'));
    }

    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $this->variantService->updateVariant($product, $variant, $request->validated());

        $this->auditLogger->logAdminEvent(
            'catalog.variant_updated',
            auth('admin')->user(),
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'sku' => $variant->sku],
            $variant
        );

        return redirect()->route('admin.products.variants.index', $product)
            ->with('success', "Variant '{$variant->sku}' updated successfully.");
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $sku = $variant->sku;
        $id = $variant->id;

        $this->variantService->deleteVariant($product, $variant);

        $this->auditLogger->logAdminEvent(
            'catalog.variant_deleted',
            auth('admin')->user(),
            ['product_id' => $product->id, 'variant_id' => $id, 'sku' => $sku],
            $product
        );

        return redirect()->route('admin.products.variants.index', $product)
            ->with('success', "Variant '{$sku}' deleted successfully.");
    }

    public function toggleStatus(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $this->variantService->toggleVariantStatus($product, $variant);

        $this->auditLogger->logAdminEvent(
            'catalog.variant_updated',
            auth('admin')->user(),
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'is_active' => $variant->is_active],
            $variant
        );

        $statusStr = $variant->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Variant '{$variant->sku}' is now {$statusStr}.");
    }
}
