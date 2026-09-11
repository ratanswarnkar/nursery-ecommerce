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

        $agreedCategorySlugs = [
            'indoor-plants',
            'flowering-plants',
            'air-purifying',
            'fruit-plants',
            'outdoor-plants',
            'herbal-medicinal-plants',
            'flowering-saplings',
            'terracotta-pots',
            'plant-care',
        ];

        $rootCategories = Category::active()
            ->whereIn('slug', $agreedCategorySlugs)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Fallback for isolated test environments if specific slugs were not pre-seeded
        if ($rootCategories->isEmpty()) {
            $rootCategories = Category::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->take(9)
                ->get();
        }

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
