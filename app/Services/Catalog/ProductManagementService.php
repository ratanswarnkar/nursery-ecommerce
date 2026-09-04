<?php

namespace App\Services\Catalog;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductManagementService
{
    public function __construct(
        protected SeoSyncService $seoSyncService,
        protected MediaStorageService $mediaStorageService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create a new product with categories, default variant, SEO, and audit logging.
     */
    public function createProduct(array $data, ?Admin $admin = null): Product
    {
        return DB::transaction(function () use ($data, $admin) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);

            // Ensure unique slug
            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $product = Product::create([
                'brand_id' => $data['brand_id'] ?? null,
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'base_sku' => strtoupper(trim($data['base_sku'])),
                'short_description' => $data['short_description'] ?? null,
                'full_description' => $data['full_description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_featured' => $data['is_featured'] ?? false,
                'custom_attributes' => $data['custom_attributes'] ?? null,
            ]);

            // Sync categories with is_primary
            $this->syncCategories($product, $data['category_ids'] ?? [], $data['primary_category_id'] ?? null);

            // Create initial default variant
            $initialPrice = $data['initial_price'] ?? null;
            if ($initialPrice !== null && $initialPrice !== '') {
                $compareAt = $data['initial_compare_at_price'] ?? null;
                $costPrice = $data['initial_cost_price'] ?? null;

                if ($compareAt !== null && bccomp((string) $compareAt, (string) $initialPrice, 2) < 0) {
                    throw ValidationException::withMessages([
                        'initial_compare_at_price' => ['The compare-at price must be greater than or equal to the selling price.'],
                    ]);
                }

                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $product->base_sku,
                    'price' => $initialPrice,
                    'compare_at_price' => $compareAt ?: null,
                    'cost_price' => $costPrice ?: null,
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }

            // Sync SEO
            $this->seoSyncService->syncSeo($product, $data);

            // Audit Log
            $this->auditLogger->logAdminEvent(
                'catalog.product_created',
                $admin,
                ['id' => $product->id, 'name' => $product->name, 'sku' => $product->base_sku],
                $product
            );

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Product $product, array $data, ?Admin $admin = null): Product
    {
        return DB::transaction(function () use ($product, $data, $admin) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);

            // Ensure unique slug excluding this product
            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug.'-'.$count++;
            }

            $product->update([
                'brand_id' => $data['brand_id'] ?? null,
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'name' => $data['name'],
                'slug' => $slug,
                'base_sku' => strtoupper(trim($data['base_sku'])),
                'short_description' => $data['short_description'] ?? null,
                'full_description' => $data['full_description'] ?? null,
                'is_active' => $data['is_active'] ?? $product->is_active,
                'is_featured' => $data['is_featured'] ?? $product->is_featured,
                'custom_attributes' => $data['custom_attributes'] ?? $product->custom_attributes,
            ]);

            // Sync categories with is_primary
            if (isset($data['category_ids'])) {
                $this->syncCategories($product, $data['category_ids'], $data['primary_category_id'] ?? null);
            }

            // Sync SEO
            $this->seoSyncService->syncSeo($product, $data);

            // Audit Log
            $this->auditLogger->logAdminEvent(
                'catalog.product_updated',
                $admin,
                ['id' => $product->id, 'changed' => $product->getChanges()],
                $product
            );

            return $product;
        });
    }

    /**
     * Delete a product safely, preserving historical orders and tenders.
     */
    public function deleteProduct(Product $product, ?Admin $admin = null): void
    {
        DB::transaction(function () use ($product, $admin) {
            $hasOrderReferences = $product->variants()
                ->where(function ($q) {
                    $q->whereHas('orderItems')
                        ->orWhereHas('tenderItems');
                })->exists();

            if ($hasOrderReferences) {
                // Strictly soft-delete and deactivate
                $product->update(['is_active' => false]);
                $product->variants()->update(['is_active' => false]);
                $product->delete();
            } else {
                // Clean up images from disk
                foreach ($product->images as $image) {
                    $this->mediaStorageService->deleteFile($image->file_path);
                    $image->delete();
                }

                $product->categories()->detach();
                $product->variants()->delete();
                $product->seoMetadata()->delete();
                $product->delete();
            }

            $this->auditLogger->logAdminEvent(
                'catalog.product_deleted',
                $admin,
                ['id' => $product->id, 'name' => $product->name, 'sku' => $product->base_sku],
                $product
            );
        });
    }

    /**
     * Helper to sync product categories and set the primary category.
     */
    private function syncCategories(Product $product, array $categoryIds, ?int $primaryCategoryId): void
    {
        if (empty($categoryIds)) {
            $product->categories()->detach();

            return;
        }

        $syncData = [];
        foreach ($categoryIds as $catId) {
            $syncData[$catId] = [
                'is_primary' => ((int) $catId === (int) $primaryCategoryId),
            ];
        }

        $product->categories()->sync($syncData);
    }
}
