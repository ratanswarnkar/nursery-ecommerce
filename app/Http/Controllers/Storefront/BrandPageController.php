<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CatalogFilterRequest;
use App\Models\Brand;
use App\Services\Storefront\StorefrontCatalogService;
use App\Services\Storefront\StorefrontSeoService;
use Illuminate\View\View;

class BrandPageController extends Controller
{
    public function __construct(
        protected StorefrontCatalogService $catalogService,
        protected StorefrontSeoService $seoService
    ) {}

    public function show(CatalogFilterRequest $request, Brand $brand): View
    {
        abort_unless($brand->is_active && ! $brand->trashed(), 404);

        $filters = array_merge($request->validated(), [
            'brand' => [$brand->slug],
        ]);

        $products = $this->catalogService->getFilteredProducts($filters, 12);
        $facets = $this->catalogService->getFilterFacets(null, $request->input('q'));

        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Shop', 'url' => route('shop.index')],
            ['name' => $brand->name, 'url' => route('brands.show', $brand->slug)],
        ];

        $breadcrumbJsonLd = $this->seoService->buildBreadcrumbJsonLd($breadcrumbs);

        return view('storefront.brand.show', [
            'brand' => $brand,
            'products' => $products,
            'facets' => $facets,
            'filters' => $filters,
            'breadcrumbs' => $breadcrumbs,
            'breadcrumbJsonLd' => $breadcrumbJsonLd,
            'canonicalUrl' => route('brands.show', $brand->slug),
        ]);
    }
}
