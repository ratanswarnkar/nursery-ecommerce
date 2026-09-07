<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Storefront\StorefrontCatalogService;
use App\Services\Storefront\StorefrontSeoService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected StorefrontCatalogService $catalogService,
        protected StorefrontSeoService $seoService
    ) {}

    public function index(): View
    {
        $featuredProducts = $this->catalogService->getFeaturedProducts(8);

        $rootCategories = Category::active()
            ->root()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(6)
            ->get();

        $newArrivals = $this->catalogService->getFilteredProducts(['sort' => 'newest'], 4);

        $organizationJsonLd = $this->seoService->buildOrganizationJsonLd();

        $seoData = [
            'title' => 'Sugandha Farms and Nursery | Wholesale Plant Nursery & Living Greenery',
            'description' => 'Acclimatized houseplants, flowering saplings, terracotta pottery, and organic soils directly from our nursery greenhouse in Delhi.',
            'canonical' => route('home'),
            'schema' => '<script type="application/ld+json">'.json_encode($organizationJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>',
        ];

        return view('storefront.home', [
            'featuredProducts' => $featuredProducts,
            'rootCategories' => $rootCategories,
            'newArrivals' => $newArrivals,
            'seoData' => $seoData,
        ]);
    }
}
