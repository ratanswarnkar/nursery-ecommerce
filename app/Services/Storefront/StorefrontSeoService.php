<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;

class StorefrontSeoService
{
    /**
     * Generate Schema.org Product and Offer JSON-LD structured data.
     */
    public function buildProductJsonLd(Product $product, ?ProductVariant $variant = null): array
    {
        $variant = ($variant && $variant->is_active) ? $variant : $product->variants->firstWhere('is_active', true);

        $price = $variant ? (string) $variant->price : '0.00';
        $inStock = $variant && $variant->is_active && $variant->available_stock > 0;

        $images = $product->images->pluck('url')->all();
        if (empty($images) && $product->primaryImage) {
            $images = [$product->primaryImage->url];
        }

        $jsonLd = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags($product->short_description ?: $product->name),
            'sku' => $variant?->sku ?: $product->base_sku,
        ];

        if (! empty($images)) {
            $jsonLd['image'] = $images;
        }

        if ($product->brand) {
            $jsonLd['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }

        $jsonLd['offers'] = [
            '@type' => 'Offer',
            'url' => route('products.show', $product->slug),
            'priceCurrency' => 'INR',
            'price' => $price,
            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];

        return $jsonLd;
    }

    /**
     * Generate Schema.org BreadcrumbList JSON-LD structured data.
     */
    public function buildBreadcrumbJsonLd(array $breadcrumbs): array
    {
        $itemList = [];
        $position = 1;

        foreach ($breadcrumbs as $crumb) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => $crumb['url'] ?? null,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }

    /**
     * Generate Schema.org Organization / LocalBusiness structured data.
     */
    public function buildOrganizationJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WholesaleStore',
            'name' => 'Sugandha Farms and Nursery',
            'description' => 'Wholesale plant nursery offering healthy plants, indoor greenery, fruit saplings, organic soils, and garden essentials in Delhi.',
            'url' => url('/'),
            'telephone' => '098111 14365',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd',
                'addressLocality' => 'Delhi',
                'addressRegion' => 'Delhi',
                'postalCode' => '110082',
                'addressCountry' => 'IN',
            ],
            'priceRange' => '₹₹',
        ];
    }
}
