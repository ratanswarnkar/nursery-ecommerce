<?php

namespace App\Services\Catalog;

use App\Enums\AttributeType;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    /**
     * Create a new variant for a product with full integrity checks and atomic default locking.
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            // Lock product variants for update to prevent concurrent default conflicts
            ProductVariant::where('product_id', $product->id)->lockForUpdate()->get();

            $attributeValueIds = array_values(array_unique(array_filter($data['attribute_value_ids'] ?? [])));
            $this->validateAttributeValues($attributeValueIds);
            $this->validateUniqueVariantCombination($product, $attributeValueIds);
            $this->validateMoneyRules($data['price'], $data['compare_at_price'] ?? null, $data['cost_price'] ?? null);

            $existingCount = ProductVariant::where('product_id', $product->id)->count();
            $shouldBeDefault = ! empty($data['is_default']) || $existingCount === 0;

            if ($shouldBeDefault) {
                ProductVariant::where('product_id', $product->id)->update(['is_default' => false]);
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'weight' => $data['weight'] ?? null,
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $shouldBeDefault,
                'custom_attributes' => $data['custom_attributes'] ?? null,
            ]);

            if (! empty($attributeValueIds)) {
                $variant->attributeValues()->sync($attributeValueIds);
            }

            return $variant;
        });
    }

    /**
     * Update an existing variant with full integrity checks and atomic default locking.
     */
    public function updateVariant(Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        abort_unless($variant->product_id === $product->id, 404);

        return DB::transaction(function () use ($product, $variant, $data) {
            ProductVariant::where('product_id', $product->id)->lockForUpdate()->get();

            $attributeValueIds = array_values(array_unique(array_filter($data['attribute_value_ids'] ?? [])));
            $this->validateAttributeValues($attributeValueIds);
            $this->validateUniqueVariantCombination($product, $attributeValueIds, $variant->id);
            $this->validateMoneyRules($data['price'], $data['compare_at_price'] ?? null, $data['cost_price'] ?? null);

            $wasDefault = $variant->is_default;
            $willBeDefault = isset($data['is_default']) ? (bool) $data['is_default'] : $wasDefault;

            // Invariant: cannot unset is_default directly on the default variant
            if ($wasDefault && ! $willBeDefault) {
                throw ValidationException::withMessages([
                    'is_default' => ['A product must always have a default variant. Set another variant as default instead.'],
                ]);
            }

            // Invariant: deactivating default variant requires another active variant
            $willBeActive = isset($data['is_active']) ? (bool) $data['is_active'] : $variant->is_active;
            if ($wasDefault && ! $willBeActive) {
                $otherActive = ProductVariant::where('product_id', $product->id)
                    ->where('id', '!=', $variant->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();

                if (! $otherActive) {
                    throw ValidationException::withMessages([
                        'is_active' => ['Cannot deactivate the default variant when no other active variants exist. Deactivate the product instead.'],
                    ]);
                }

                // Promote the other active variant to default
                $otherActive->update(['is_default' => true]);
                $willBeDefault = false;
            }

            if ($willBeDefault && ! $wasDefault) {
                ProductVariant::where('product_id', $product->id)->where('id', '!=', $variant->id)->update(['is_default' => false]);
            }

            $variant->update([
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'weight' => $data['weight'] ?? null,
                'length' => $data['length'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'is_active' => $willBeActive,
                'is_default' => $willBeDefault,
                'custom_attributes' => $data['custom_attributes'] ?? $variant->custom_attributes,
            ]);

            $variant->attributeValues()->sync($attributeValueIds);

            return $variant;
        });
    }

    /**
     * Delete a variant safely, respecting order references and default variant promotion.
     */
    public function deleteVariant(Product $product, ProductVariant $variant): void
    {
        abort_unless($variant->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $variant) {
            ProductVariant::where('product_id', $product->id)->lockForUpdate()->get();

            $totalVariants = ProductVariant::where('product_id', $product->id)->count();
            if ($totalVariants <= 1) {
                throw ValidationException::withMessages([
                    'variant' => ['Cannot delete the only variant of a product. Deactivate or delete the product instead.'],
                ]);
            }

            if ($variant->is_default) {
                // Promote earliest active variant, or earliest variant if none active
                $nextDefault = ProductVariant::where('product_id', $product->id)
                    ->where('id', '!=', $variant->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first()
                    ?? ProductVariant::where('product_id', $product->id)
                        ->where('id', '!=', $variant->id)
                        ->orderBy('id')
                        ->first();

                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true]);
                }
            }

            // Check for references in order_items, cart_items, stock_movements, tender_items
            $hasOrderReferences = $variant->orderItems()->exists()
                || $variant->tenderItems()->exists()
                || $variant->stockMovements()->exists();

            if ($hasOrderReferences) {
                // Strictly soft-delete and deactivate to preserve historical ledger
                $variant->update(['is_active' => false, 'is_default' => false]);
                $variant->delete();
            } else {
                $variant->attributeValues()->detach();
                $variant->delete();
            }
        });
    }

    /**
     * Toggle variant active status safely.
     */
    public function toggleVariantStatus(Product $product, ProductVariant $variant): ProductVariant
    {
        abort_unless($variant->product_id === $product->id, 404);

        return DB::transaction(function () use ($product, $variant) {
            ProductVariant::where('product_id', $product->id)->lockForUpdate()->get();

            if ($variant->is_active && $variant->is_default) {
                // Attempting to deactivate default variant: check if another active variant exists
                $otherActive = ProductVariant::where('product_id', $product->id)
                    ->where('id', '!=', $variant->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();

                if (! $otherActive) {
                    throw ValidationException::withMessages([
                        'status' => ['Cannot deactivate the default variant when no other active variants exist. Deactivate the product instead.'],
                    ]);
                }

                $otherActive->update(['is_default' => true]);
                $variant->update(['is_active' => false, 'is_default' => false]);
            } else {
                $variant->update(['is_active' => ! $variant->is_active]);
            }

            return $variant->fresh();
        });
    }

    /**
     * Validate money rules using decimal-safe comparison.
     */
    public function validateMoneyRules(mixed $price, mixed $compareAtPrice, mixed $costPrice): void
    {
        $priceStr = (string) $price;

        if (bccomp($priceStr, '0', 2) < 0) {
            throw ValidationException::withMessages([
                'price' => ['The selling price must be greater than or equal to 0.'],
            ]);
        }

        if ($compareAtPrice !== null && $compareAtPrice !== '') {
            $compareAtStr = (string) $compareAtPrice;

            if (bccomp($compareAtStr, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'compare_at_price' => ['The compare-at price must be greater than or equal to 0.'],
                ]);
            }

            // compare_at_price must be >= price
            if (bccomp($compareAtStr, $priceStr, 2) < 0) {
                throw ValidationException::withMessages([
                    'compare_at_price' => ['The compare-at price (MRP) must be greater than or equal to the selling price.'],
                ]);
            }
        }

        if ($costPrice !== null && $costPrice !== '') {
            $costStr = (string) $costPrice;

            if (bccomp($costStr, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'cost_price' => ['The cost price must be greater than or equal to 0.'],
                ]);
            }
        }
    }

    /**
     * Verify attribute values ownership and constraints.
     */
    public function validateAttributeValues(array $attributeValueIds): void
    {
        if (empty($attributeValueIds)) {
            return;
        }

        $values = AttributeValue::with('attribute')->whereIn('id', $attributeValueIds)->get();

        if ($values->count() !== count($attributeValueIds)) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => ['One or more selected attribute values are invalid or do not exist.'],
            ]);
        }

        foreach ($values as $val) {
            if (! in_array($val->attribute->type, [AttributeType::SELECT, AttributeType::MULTISELECT], true)) {
                throw ValidationException::withMessages([
                    'attribute_value_ids' => [
                        "Attribute '{$val->attribute->name}' of type '{$val->attribute->type->value}' does not support selectable options. Values for boolean, text, or numeric attributes belong in custom attributes.",
                    ],
                ]);
            }
        }

        $dimensionCounts = $values->groupBy('attribute_id')->map->count();
        foreach ($dimensionCounts as $attrId => $count) {
            if ($count > 1) {
                $attrName = $values->firstWhere('attribute_id', $attrId)?->attribute?->name ?? 'Attribute';
                throw ValidationException::withMessages([
                    'attribute_value_ids' => ["A variant cannot have more than one value for the '{$attrName}' attribute."],
                ]);
            }
        }
    }

    /**
     * Prevent duplicate variant attribute combinations on the same product.
     */
    public function validateUniqueVariantCombination(Product $product, array $attributeValueIds, ?int $excludeVariantId = null): void
    {
        if (empty($attributeValueIds)) {
            return;
        }

        sort($attributeValueIds);

        $existingVariants = ProductVariant::where('product_id', $product->id)
            ->when($excludeVariantId, fn ($q) => $q->where('id', '!=', $excludeVariantId))
            ->with('attributeValues')
            ->get();

        foreach ($existingVariants as $existing) {
            $existingIds = $existing->attributeValues->pluck('id')->all();
            sort($existingIds);

            if ($existingIds === $attributeValueIds) {
                throw ValidationException::withMessages([
                    'attribute_value_ids' => ['A variant with this exact combination of attribute values already exists for this product.'],
                ]);
            }
        }
    }
}
