<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Storefront\StorefrontCatalogService;
use App\Services\Storefront\StorefrontSeoService;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    public function __construct(
        protected StorefrontCatalogService $catalogService,
        protected StorefrontSeoService $seoService
    ) {}

    public function show(Product $product): View
    {
        abort_unless($product->is_active && ! $product->trashed(), 404);

        $product->load([
            'images',
            'primaryImage',
            'brand',
            'categories',
            'variants.attributeValues.attribute',
            'variants.inventories.warehouse',
            'variants.images',
            'seoMetadata',
        ]);

        // Filter variants to active, non-deleted only
        $activeVariants = $product->variants->filter(fn ($v) => $v->is_active && ! $v->trashed());

        $defaultVariant = $activeVariants->firstWhere('is_default', true) ?: $activeVariants->first();

        // 1. Build Server-Generated Variant Matrix (Only real active database variants)
        $variantMatrix = $activeVariants->map(function ($variant) use ($product) {
            $attributeMap = [];
            foreach ($variant->attributeValues as $av) {
                $attributeMap[$av->attribute_id] = $av->id;
            }

            $variantImage = $variant->images->first()?->url ?: ($product->primaryImage?->url ?: null);

            $safetyStock = (int) $variant->inventories
                ->filter(fn ($inv) => ! $inv->warehouse || $inv->warehouse->is_active)
                ->sum('safety_stock');

            $availableStock = (int) $variant->available_stock;
            $isLowStock = ($safetyStock > 0 && $availableStock > 0 && $availableStock <= $safetyStock);

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => (float) $variant->price,
                'price_formatted' => '₹'.number_format((float) $variant->price, 2),
                'compare_at_price' => $variant->compare_at_price ? (float) $variant->compare_at_price : null,
                'compare_at_price_formatted' => $variant->compare_at_price ? '₹'.number_format((float) $variant->compare_at_price, 2) : null,
                'discount_percentage' => $variant->discount_percentage,
                'discount_percent' => $variant->discount_percentage,
                'available_stock' => $availableStock,
                'stock' => $availableStock,
                'safety_stock' => $safetyStock,
                'is_in_stock' => $availableStock > 0,
                'is_low_stock' => $isLowStock,
                'stock_status' => $availableStock <= 0 ? 'out_of_stock' : ($isLowStock ? 'low_stock' : 'in_stock'),
                'max_quantity' => min(50, max(0, $availableStock)),
                'image_url' => $variantImage,
                'attributes' => $attributeMap,
                'attribute_values' => $attributeMap,
            ];
        })->values();

        // 2. Extract Distinct Attribute Dimensions Present on Active Variants
        $attributeDimensions = collect();
        foreach ($activeVariants as $variant) {
            foreach ($variant->attributeValues as $av) {
                $attr = $av->attribute;
                if (! $attr) {
                    continue;
                }

                if (! $attributeDimensions->has($attr->id)) {
                    $attributeDimensions->put($attr->id, [
                        'id' => $attr->id,
                        'name' => $attr->name,
                        'code' => $attr->code,
                        'values' => collect(),
                    ]);
                }

                $dim = $attributeDimensions->get($attr->id);
                if (! $dim['values']->contains('id', $av->id)) {
                    $dim['values']->push([
                        'id' => $av->id,
                        'label' => $av->label,
                        'value' => $av->value,
                    ]);
                }
                $attributeDimensions->put($attr->id, $dim);
            }
        }

        // 3. Related Products
        $relatedProducts = $this->catalogService->getRelatedProducts($product, 4);

        // 4. Breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Shop', 'url' => route('shop.index')],
        ];

        $primaryCategory = $product->categories()->wherePivot('is_primary', true)->first() ?: $product->categories->first();
        if ($primaryCategory) {
            $breadcrumbs[] = [
                'name' => $primaryCategory->name,
                'url' => route('categories.show', $primaryCategory->slug),
            ];
        }

        $breadcrumbs[] = [
            'name' => $product->name,
            'url' => route('products.show', $product->slug),
        ];

        // 5. Schema.org JSON-LD Structured Data
        $productJsonLd = $this->seoService->buildProductJsonLd($product, $defaultVariant);
        $breadcrumbJsonLd = $this->seoService->buildBreadcrumbJsonLd($breadcrumbs);

        $seoData = [
            'title' => ($product->name.' | Sugandha Farms and Nursery'),
            'description' => strip_tags($product->short_description ?: $product->name),
            'canonical' => route('products.show', $product->slug),
            'schema' => '<script type="application/ld+json">'.json_encode($productJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>'."\n".
                        '<script type="application/ld+json">'.json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>',
        ];

        return view('storefront.product.show', [
            'product' => $product,
            'activeVariants' => $activeVariants,
            'defaultVariant' => $defaultVariant,
            'defaultVariantId' => $defaultVariant?->id,
            'variantMatrix' => $variantMatrix,
            'attributeDimensions' => $attributeDimensions->values(),
            'optionDimensions' => $attributeDimensions->values()->all(),
            'relatedProducts' => $relatedProducts,
            'breadcrumbs' => $breadcrumbs,
            'productJsonLd' => $productJsonLd,
            'breadcrumbJsonLd' => $breadcrumbJsonLd,
            'seoData' => $seoData,
            'canonicalUrl' => route('products.show', $product->slug),
        ]);
    }
}
