<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageReorderRequest;
use App\Http\Requests\Admin\ProductImageUploadRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductImageController extends Controller
{
    public function __construct(
        protected MediaStorageService $mediaStorageService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Product $product): View
    {
        $images = $product->images()
            ->with('variant')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $variants = $product->variants()
            ->orderBy('sku')
            ->get(['id', 'sku']);

        return view('admin.products.images.index', compact('product', 'images', 'variants'));
    }

    public function store(ProductImageUploadRequest $request, Product $product): RedirectResponse
    {
        $uploadedFiles = $request->file('images', []);
        $variantId = $request->input('product_variant_id');
        $isExplicitPrimary = (bool) $request->input('is_primary');

        DB::transaction(function () use ($product, $uploadedFiles, $variantId, $isExplicitPrimary, $request) {
            Product::where('id', $product->id)->lockForUpdate()->first();

            $existingImagesCount = ProductImage::where('product_id', $product->id)->count();
            $currentMaxSort = ProductImage::where('product_id', $product->id)->max('sort_order') ?? 0;

            $hasPrimary = ProductImage::where('product_id', $product->id)->where('is_primary', true)->exists();

            $storedPaths = [];

            try {
                foreach ($uploadedFiles as $index => $file) {
                    $path = $this->mediaStorageService->storeProductImage($file, $product->id);
                    $storedPaths[] = $path;

                    // Designate primary if explicit or if product currently has zero images
                    $shouldBePrimary = ($index === 0 && ($isExplicitPrimary || ! $hasPrimary));

                    if ($shouldBePrimary) {
                        ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);
                        $hasPrimary = true;
                    }

                    ProductImage::create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variantId ?: null,
                        'file_path' => $path,
                        'alt_text' => $request->input('alt_text') ?: $product->name,
                        'sort_order' => $currentMaxSort + $index + 1,
                        'is_primary' => $shouldBePrimary,
                    ]);
                }
            } catch (\Throwable $e) {
                // Clean up any files already written if a DB error occurs
                foreach ($storedPaths as $p) {
                    $this->mediaStorageService->deleteFile($p);
                }
                throw $e;
            }

            $this->auditLogger->logAdminEvent(
                'catalog.image_uploaded',
                auth('admin')->user(),
                ['product_id' => $product->id, 'count' => count($uploadedFiles)],
                $product
            );
        });

        return redirect()->route('admin.products.images.index', $product)
            ->with('success', count($uploadedFiles).' image(s) uploaded successfully.');
    }

    public function setPrimary(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            Product::where('id', $product->id)->lockForUpdate()->first();

            ProductImage::where('product_id', $product->id)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);

            $image->update(['is_primary' => true]);

            $this->auditLogger->logAdminEvent(
                'catalog.image_primary_set',
                auth('admin')->user(),
                ['product_id' => $product->id, 'image_id' => $image->id],
                $image
            );
        });

        return redirect()->back()
            ->with('success', 'Primary image updated successfully.');
    }

    public function reorder(ProductImageReorderRequest $request, Product $product): RedirectResponse
    {
        $imageIds = $request->input('image_ids', []);

        DB::transaction(function () use ($product, $imageIds) {
            Product::where('id', $product->id)->lockForUpdate()->first();

            foreach ($imageIds as $order => $id) {
                ProductImage::where('product_id', $product->id)
                    ->where('id', $id)
                    ->update(['sort_order' => $order]);
            }

            $this->auditLogger->logAdminEvent(
                'catalog.images_reordered',
                auth('admin')->user(),
                ['product_id' => $product->id, 'order' => $imageIds],
                $product
            );
        });

        return redirect()->back()
            ->with('success', 'Images reordered successfully.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            Product::where('id', $product->id)->lockForUpdate()->first();

            $wasPrimary = $image->is_primary;
            $filePath = $image->file_path;
            $imageId = $image->id;

            $image->delete();
            $this->mediaStorageService->deleteFile($filePath);

            if ($wasPrimary) {
                $nextPrimary = ProductImage::where('product_id', $product->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                $nextPrimary?->update(['is_primary' => true]);
            }

            $this->auditLogger->logAdminEvent(
                'catalog.image_deleted',
                auth('admin')->user(),
                ['product_id' => $product->id, 'image_id' => $imageId],
                $product
            );
        });

        return redirect()->route('admin.products.images.index', $product)
            ->with('success', 'Image deleted successfully.');
    }
}
