<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate an authentic dynamic XML sitemap for search engines.
     * Only public indexable URLs are included. Private customer,
     * cart, checkout, admin, and authentication routes are strictly excluded.
     */
    public function index(): Response
    {
        $urls = [];

        // 1. Core Public Pages
        $urls[] = [
            'loc' => route('home'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        $urls[] = [
            'loc' => route('shop.index'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

        // 2. Public Policy & Information Pages
        $policyRoutes = [
            'policy.shipping',
            'policy.contact',
            'policy.refund',
            'policy.terms',
            'policy.privacy',
        ];

        foreach ($policyRoutes as $routeName) {
            $urls[] = [
                'loc' => route($routeName),
                'lastmod' => now()->startOfMonth()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        // 3. Active Categories
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => route('categories.show', $category->slug),
                'lastmod' => $category->updated_at?->toAtomString() ?? now()->startOfWeek()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 4. Active Brands
        $brands = Brand::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        foreach ($brands as $brand) {
            $urls[] = [
                'loc' => route('brands.show', $brand->slug),
                'lastmod' => $brand->updated_at?->toAtomString() ?? now()->startOfWeek()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // 5. Active Products (with at least one active variant)
        $products = Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))
            ->orderBy('name')
            ->get();

        foreach ($products as $product) {
            $urls[] = [
                'loc' => route('products.show', $product->slug),
                'lastmod' => $product->updated_at?->toAtomString() ?? now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        // Render XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>'.htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8')."</loc>\n";
            $xml .= '        <lastmod>'.$url['lastmod']."</lastmod>\n";
            $xml .= '        <changefreq>'.$url['changefreq']."</changefreq>\n";
            $xml .= '        <priority>'.$url['priority']."</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex', // Sitemap itself doesn't need to be indexed as content
        ]);
    }
}
