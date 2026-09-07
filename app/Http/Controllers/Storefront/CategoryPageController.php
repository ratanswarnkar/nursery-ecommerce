<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CatalogFilterRequest;
use App\Models\Category;
use App\Services\Storefront\StorefrontCatalogService;
use App\Services\Storefront\StorefrontSeoService;
use Illuminate\View\View;

class CategoryPageController extends Controller
{
    public function __construct(
        protected StorefrontCatalogService $catalogService,
        protected StorefrontSeoService $seoService
    ) {}

    public function show(CatalogFilterRequest $request, Category $category): View
    {
        abort_unless($category->is_active && ! $category->trashed(), 404);

        $filters = array_merge($request->validated(), [
            'category' => $category->slug,
        ]);

        $products = $this->catalogService->getFilteredProducts($filters, 12);
        $facets = $this->catalogService->getFilterFacets($category, $request->input('q'));

        // Subcategories for quick category navigation tiles
        $subcategories = $category->children()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Build hierarchical breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Shop', 'url' => route('shop.index')],
        ];

        $ancestors = collect();
        $curr = $category->parent;
        while ($curr) {
            if ($curr->is_active && ! $curr->trashed()) {
                $ancestors->prepend([
                    'name' => $curr->name,
                    'url' => route('categories.show', $curr->slug),
                ]);
            }
            $curr = $curr->parent;
        }

        foreach ($ancestors as $ancestor) {
            $breadcrumbs[] = $ancestor;
        }

        $breadcrumbs[] = [
            'name' => $category->name,
            'url' => route('categories.show', $category->slug),
        ];

        $breadcrumbJsonLd = $this->seoService->buildBreadcrumbJsonLd($breadcrumbs);

        $seoData = [
            'title' => $category->name.' | Sugandha Farms and Nursery',
            'description' => $category->description ?: 'Browse healthy '.$category->name.' plants.',
            'canonical' => route('categories.show', $category->slug),
            'schema' => '<script type="application/ld+json">'.json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>',
        ];

        return view('storefront.category.show', [
            'category' => $category,
            'ancestors' => $ancestors,
            'subcategories' => $subcategories,
            'products' => $products,
            'facets' => $facets,
            'filters' => $filters,
            'breadcrumbs' => $breadcrumbs,
            'breadcrumbJsonLd' => $breadcrumbJsonLd,
            'seoData' => $seoData,
            'canonicalUrl' => route('categories.show', $category->slug),
        ]);
    }
}
