<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CatalogFilterRequest;
use App\Services\Storefront\StorefrontCatalogService;
use App\Services\Storefront\StorefrontSeoService;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(
        protected StorefrontCatalogService $catalogService,
        protected StorefrontSeoService $seoService
    ) {}

    public function index(CatalogFilterRequest $request): View
    {
        $filters = $request->validated();
        $products = $this->catalogService->getFilteredProducts($filters, 12);
        $facets = $this->catalogService->getFilterFacets(null, $request->input('q'));

        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'All Plants & Garden Essentials', 'url' => route('shop.index')],
        ];

        $hasActiveFilters = ! empty($filters['category']) ||
            ! empty($filters['brand']) ||
            ! empty($filters['attributes']) ||
            ! empty($filters['min_price']) ||
            ! empty($filters['max_price']) ||
            ! empty($filters['in_stock']) ||
            ! empty($filters['q']);

        return view('storefront.shop.index', [
            'products' => $products,
            'facets' => $facets,
            'filters' => $filters,
            'breadcrumbs' => $breadcrumbs,
            'canonicalUrl' => route('shop.index'),
            'pageTitle' => ! empty($filters['q']) ? 'Search: '.$filters['q'] : 'Shop All Plants & Nursery Essentials',
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }
}
