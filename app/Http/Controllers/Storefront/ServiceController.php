<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\StorefrontSeoService;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        protected StorefrontSeoService $seoService
    ) {}

    /**
     * Display the Landscaping Services page.
     * Communicates authentic botanical and landscape solutions across Delhi NCR.
     */
    public function landscaping(): View
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Services', 'url' => route('services.landscaping')],
            ['name' => 'Landscaping Services', 'url' => route('services.landscaping')],
        ];

        $serviceJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => 'Professional Landscaping Services',
            'serviceType' => 'Garden Design, Plantation & Landscape Development',
            'provider' => [
                '@type' => 'GardenStore',
                'name' => 'Sugandha Farms and Nursery',
                'telephone' => '+91-9811114365',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd',
                    'addressLocality' => 'Delhi',
                    'postalCode' => '110082',
                    'addressRegion' => 'Delhi',
                    'addressCountry' => 'IN',
                ],
            ],
            'areaServed' => [
                '@type' => 'AdministrativeArea',
                'name' => 'Delhi NCR',
            ],
            'description' => 'Comprehensive residential and commercial landscape design, garden development, lawn installation, and plant maintenance in Delhi NCR.',
        ];

        $seoData = [
            'title' => 'Landscaping Services in Delhi NCR | Sugandha Farms and Nursery',
            'description' => 'Professional landscape design, residential & commercial garden development, lawn installation, and garden maintenance across Delhi NCR by Sugandha Farms and Nursery.',
            'canonical' => route('services.landscaping'),
            'schema' => '<script type="application/ld+json">'.json_encode($serviceJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>',
        ];

        $serviceOfferings = [
            [
                'title' => 'Garden Landscaping',
                'description' => 'Complete site layout planning, topographical soil grading, drainage structuring, and climatic botanical curation tailored specifically to Delhi NCR weather.',
                'icon' => 'garden',
            ],
            [
                'title' => 'Residential Landscaping',
                'description' => 'Customized landscape solutions for private villas, terrace gardens, balcony greening, and courtyard planting to create restorative outdoor living spaces.',
                'icon' => 'residential',
            ],
            [
                'title' => 'Commercial Landscaping',
                'description' => 'Greenery installations and environmental beautification for corporate offices, hospitality campuses, institutional grounds, and retail developments.',
                'icon' => 'commercial',
            ],
            [
                'title' => 'Lawn & Garden Development',
                'description' => 'Healthy natural lawn turf development, ground conditioning, decorative border hedges, pathway greening, and erosion-resistant grass bedding.',
                'icon' => 'lawn',
            ],
            [
                'title' => 'Planting & Plantation',
                'description' => 'Direct sourcing, transportation, and root-safe transplanting of mature shade trees, seasonal flower borders, architectural hedges, and fruiting groves.',
                'icon' => 'planting',
            ],
            [
                'title' => 'Garden Maintenance',
                'description' => 'Ongoing horticultural care including seasonal pruning, natural pest prevention, soil re-fertilization, compost aeration, and scheduled irrigation reviews.',
                'icon' => 'maintenance',
            ],
        ];

        return view('storefront.services.landscaping', [
            'seoData' => $seoData,
            'breadcrumbs' => $breadcrumbs,
            'serviceOfferings' => $serviceOfferings,
            'canonicalUrl' => route('services.landscaping'),
        ]);
    }
}
