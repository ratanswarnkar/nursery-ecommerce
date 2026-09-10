<?php

namespace App\Services\Storefront;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CategoryHierarchyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorefrontCatalogService
{
    public function __construct(
        protected CategoryHierarchyService $categoryHierarchyService
    ) {}

    /**
     * Build the base active product query for storefront.
     */
    public function getBaseQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('is_active', true));
    }

    /**
     * Retrieve paginated products with all multi-facet filters, search, and sorting.
     */
    public function getFilteredProducts(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->getBaseQuery();

        // 1. Eager Loading
        $query->with([
            'primaryImage',
            'brand' => fn ($q) => $q->where('is_active', true),
            'categories' => fn ($q) => $q->where('is_active', true),
            'defaultVariant' => fn ($q) => $q->where('is_active', true),
            'variants' => fn ($q) => $q->where('is_active', true),
        ]);

        // 2. Category Scoping (including all active subcategories)
        if (! empty($filters['category'])) {
            $category = Category::where('slug', $filters['category'])->where('is_active', true)->first();
            if ($category) {
                $categoryIds = array_merge([$category->id], $this->categoryHierarchyService->getActiveDescendantCategoryIds($category->id));
                $query->whereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $categoryIds)->where('is_active', true));
            } else {
                // If requested category slug is inactive or doesn't exist, produce empty results safely
                $query->whereRaw('1 = 0');
            }
        }

        // 3. Brand Filtering (Active brands only)
        if (! empty($filters['brand'])) {
            $brandSlugs = (array) $filters['brand'];
            $validBrandIds = Brand::whereIn('slug', $brandSlugs)->where('is_active', true)->pluck('id')->all();
            if (! empty($validBrandIds)) {
                $query->whereIn('brand_id', $validBrandIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // 4. Attribute Filtering (AND across dimensions, OR within dimension)
        if (! empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeId => $valueIds) {
                if (! is_numeric($attributeId) || empty($valueIds)) {
                    continue;
                }

                // Verify attribute exists and is marked filterable
                $attribute = Attribute::where('id', (int) $attributeId)->where('is_filterable', true)->first();
                if (! $attribute) {
                    continue;
                }

                // Verify attribute values belong to this attribute
                $validValueIds = AttributeValue::where('attribute_id', $attribute->id)
                    ->whereIn('id', (array) $valueIds)
                    ->pluck('id')
                    ->all();

                if (empty($validValueIds)) {
                    continue;
                }

                // Enforce AND across dimensions, OR within dimension
                $query->whereHas('variants', function ($vq) use ($validValueIds) {
                    $vq->where('is_active', true)
                        ->whereHas('attributeValues', function ($avq) use ($validValueIds) {
                            $avq->whereIn('attribute_values.id', $validValueIds);
                        });
                });
            }
        }

        // 5. Price Filtering
        if (isset($filters['min_price']) && is_numeric($filters['min_price']) && (float) $filters['min_price'] >= 0) {
            $minPrice = (float) $filters['min_price'];
            $query->whereHas('variants', fn ($vq) => $vq->where('is_active', true)->where('price', '>=', $minPrice));
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (float) $filters['max_price'] >= 0) {
            $maxPrice = (float) $filters['max_price'];
            $query->whereHas('variants', fn ($vq) => $vq->where('is_active', true)->where('price', '<=', $maxPrice));
        }

        // 6. Availability / In-Stock Filter
        if (! empty($filters['in_stock'])) {
            $query->whereHas('variants', function ($vq) {
                $vq->where('is_active', true)
                    ->whereHas('inventories', function ($iq) {
                        $iq->whereHas('warehouse', fn ($wh) => $wh->where('is_active', true))
                            ->whereRaw('quantity > reserved_quantity');
                    });
            });
        }

        // 7. Parameterized Keyword Search
        if (! empty($filters['q']) && is_string($filters['q'])) {
            $term = mb_substr(trim($filters['q']), 0, 100);
            if ($term !== '') {
                $query->where(function ($sq) use ($term) {
                    $sq->where('name', 'LIKE', '%'.$term.'%')
                        ->orWhere('base_sku', 'LIKE', '%'.$term.'%')
                        ->orWhere('short_description', 'LIKE', '%'.$term.'%')
                        ->orWhere('full_description', 'LIKE', '%'.$term.'%')
                        ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'LIKE', '%'.$term.'%'))
                        ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'LIKE', '%'.$term.'%'))
                        ->orWhereHas('categories', fn ($cq) => $cq->where('name', 'LIKE', '%'.$term.'%'));
                });
            }
        }

        // 8. Whitelisted Sorting
        $sortWhitelist = ['featured', 'price_asc', 'price_desc', 'newest', 'name_asc'];
        $sort = in_array($filters['sort'] ?? '', $sortWhitelist, true) ? $filters['sort'] : 'featured';

        switch ($sort) {
            case 'price_asc':
                $query->addSelect([
                    'min_variant_price' => ProductVariant::selectRaw('MIN(price)')
                        ->whereColumn('product_id', 'products.id')
                        ->where('is_active', true),
                ])->orderBy('min_variant_price', 'asc')->orderBy('id', 'desc');
                break;
            case 'price_desc':
                $query->addSelect([
                    'min_variant_price' => ProductVariant::selectRaw('MIN(price)')
                        ->whereColumn('product_id', 'products.id')
                        ->where('is_active', true),
                ])->orderBy('min_variant_price', 'desc')->orderBy('id', 'desc');
                break;
            case 'newest':
                $query->latest('created_at')->orderBy('id', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc')->orderBy('id', 'desc');
                break;
            case 'featured':
            default:
                $query->orderByDesc('is_featured')->latest('id');
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Compute filter facets and counts for sidebar based on the current base scope (Category & Search).
     */
    public function getFilterFacets(?Category $scopedCategory = null, ?string $searchTerm = null): array
    {
        $baseQuery = $this->getBaseQuery();

        if ($scopedCategory && $scopedCategory->is_active) {
            $categoryIds = array_merge([$scopedCategory->id], $this->categoryHierarchyService->getActiveDescendantCategoryIds($scopedCategory->id));
            $baseQuery->whereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $categoryIds));
        }

        if (! empty($searchTerm)) {
            $term = mb_substr(trim($searchTerm), 0, 100);
            if ($term !== '') {
                $baseQuery->where(function ($sq) use ($term) {
                    $sq->where('name', 'LIKE', '%'.$term.'%')
                        ->orWhere('base_sku', 'LIKE', '%'.$term.'%')
                        ->orWhere('short_description', 'LIKE', '%'.$term.'%')
                        ->orWhere('full_description', 'LIKE', '%'.$term.'%')
                        ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'LIKE', '%'.$term.'%'))
                        ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'LIKE', '%'.$term.'%'))
                        ->orWhereHas('categories', fn ($cq) => $cq->where('name', 'LIKE', '%'.$term.'%'));
                });
            }
        }

        $baseProductIds = (clone $baseQuery)->pluck('id')->all();

        // 1. Categories with counts
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function ($cat) use ($baseProductIds) {
                $count = DB::table('product_categories')
                    ->where('category_id', $cat->id)
                    ->whereIn('product_id', $baseProductIds)
                    ->count();

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'parent_id' => $cat->parent_id,
                    'count' => $count,
                ];
            });

        // 2. Brands with counts
        $brands = Brand::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($brand) use ($baseProductIds) {
                $count = Product::where('brand_id', $brand->id)
                    ->whereIn('id', $baseProductIds)
                    ->count();

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'count' => $count,
                ];
            })
            ->filter(fn ($b) => $b['count'] > 0);

        // 3. Filterable Attributes with Values and Counts
        $attributes = Attribute::where('is_filterable', true)
            ->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('label')])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($attr) use ($baseProductIds) {
                $values = $attr->values->map(function ($val) use ($baseProductIds) {
                    $count = DB::table('product_variant_attribute_values')
                        ->join('product_variants', 'product_variants.id', '=', 'product_variant_attribute_values.product_variant_id')
                        ->where('product_variant_attribute_values.attribute_value_id', $val->id)
                        ->where('product_variants.is_active', true)
                        ->whereIn('product_variants.product_id', $baseProductIds)
                        ->distinct('product_variants.product_id')
                        ->count('product_variants.product_id');

                    return [
                        'id' => $val->id,
                        'label' => $val->label,
                        'value' => $val->value,
                        'count' => $count,
                    ];
                })->filter(fn ($v) => $v['count'] > 0);

                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'code' => $attr->code,
                    'values' => $values,
                ];
            })->filter(fn ($a) => $a['values']->isNotEmpty());

        // 4. Price Boundaries
        $priceMin = ProductVariant::whereIn('product_id', $baseProductIds)
            ->where('is_active', true)
            ->min('price') ?? '0.00';

        $priceMax = ProductVariant::whereIn('product_id', $baseProductIds)
            ->where('is_active', true)
            ->max('price') ?? '5000.00';

        return [
            'categories' => $categories,
            'brands' => $brands,
            'attributes' => $attributes,
            'price_bounds' => [
                'min' => (float) $priceMin,
                'max' => (float) $priceMax,
            ],
        ];
    }

    /**
     * Retrieve featured active products for storefront home page.
     */
    public function getFeaturedProducts(int $limit = 8): Collection
    {
        return $this->getBaseQuery()
            ->where('is_featured', true)
            ->with([
                'primaryImage',
                'brand' => fn ($q) => $q->where('is_active', true),
                'defaultVariant' => fn ($q) => $q->where('is_active', true),
                'variants' => fn ($q) => $q->where('is_active', true),
            ])
            ->latest('id')
            ->take($limit)
            ->get();
    }

    /**
     * Retrieve related products sharing primary category or brand.
     */
    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        $primaryCategoryId = $product->categories()->wherePivot('is_primary', true)->value('categories.id');

        return $this->getBaseQuery()
            ->where('products.id', '!=', $product->id)
            ->where(function ($q) use ($product, $primaryCategoryId) {
                if ($primaryCategoryId) {
                    $q->whereHas('categories', fn ($cq) => $cq->where('categories.id', $primaryCategoryId));
                }
                if ($product->brand_id) {
                    $q->orWhere('brand_id', $product->brand_id);
                }
            })
            ->with([
                'primaryImage',
                'brand' => fn ($q) => $q->where('is_active', true),
                'defaultVariant' => fn ($q) => $q->where('is_active', true),
                'variants' => fn ($q) => $q->where('is_active', true),
            ])
            ->take($limit)
            ->get();
    }
}
