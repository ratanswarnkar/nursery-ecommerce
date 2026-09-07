<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\TaxClass;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StorefrontSampleSeeder extends Seeder
{
    public function run(): void
    {
        $taxClass = TaxClass::firstOrCreate(
            ['name' => 'Standard GST 18%'],
            ['description' => 'Standard GST 18%', 'is_active' => true]
        );

        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'MAIN-WH-01'],
            [
                'name' => 'Main Central Warehouse',
                'address_line_1' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'postal_code' => '110082',
                'country' => 'India',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Ensure storage directory for product images exists
        $storageDir = storage_path('app/public/products');
        if (!File::isDirectory($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        // Copy a sample image for testing
        $sampleSource = public_path('images/hero-botanical-plants.jpg');
        $sampleTarget = $storageDir . '/sample-plant.jpg';
        if (File::exists($sampleSource) && !File::exists($sampleTarget)) {
            File::copy($sampleSource, $sampleTarget);
        }

        // 1. Categories
        $indoor = Category::firstOrCreate(
            ['slug' => 'indoor-plants'],
            ['name' => 'Indoor Plants', 'description' => 'Flourishing low-maintenance houseplants suited for homes and workspaces.', 'is_active' => true, 'sort_order' => 1]
        );

        $airPurifying = Category::firstOrCreate(
            ['slug' => 'air-purifying'],
            ['name' => 'Air Purifying Plants', 'parent_id' => $indoor->id, 'description' => 'NASA-recommended foliage that filters airborne toxins naturally.', 'is_active' => true, 'sort_order' => 1]
        );

        $foliage = Category::firstOrCreate(
            ['slug' => 'foliage-plants'],
            ['name' => 'Lush Foliage Plants', 'parent_id' => $indoor->id, 'description' => 'Broad-leafed sculptural greens with vibrant chlorophyll patterns.', 'is_active' => true, 'sort_order' => 2]
        );

        $flowering = Category::firstOrCreate(
            ['slug' => 'flowering-plants'],
            ['name' => 'Flowering Plants', 'description' => 'Seasonal and perennial blooms that bring color and fragrance.', 'is_active' => true, 'sort_order' => 2]
        );

        $planters = Category::firstOrCreate(
            ['slug' => 'planters-pots'],
            ['name' => 'Planters & Pottery', 'description' => 'Artisanal earthenware, breathable terracotta, and glazed ceramic vessels.', 'is_active' => true, 'sort_order' => 3]
        );

        $terracottaCategory = Category::firstOrCreate(
            ['slug' => 'terracotta-pots'],
            ['name' => 'Terracotta Pots', 'parent_id' => $planters->id, 'description' => 'High-fired porous clay planters that keep root systems well-aerated.', 'is_active' => true, 'sort_order' => 1]
        );

        $plantCare = Category::firstOrCreate(
            ['slug' => 'plant-care'],
            ['name' => 'Plant Care & Soils', 'description' => 'Organic composts, slow-release bio-nutrients, and airy peat-free blends.', 'is_active' => true, 'sort_order' => 4]
        );

        // 2. Brands
        $brandVerdant = Brand::firstOrCreate(
            ['slug' => 'verdant-living'],
            ['name' => 'Verdant Living', 'description' => 'Acclimatized indoor botanical specimens nurtured in organic soil blends.', 'is_active' => true]
        );

        $brandTerra = Brand::firstOrCreate(
            ['slug' => 'terra-haven'],
            ['name' => 'Terra Haven', 'description' => 'Hardy air-purifying varieties propagated for resilient home adaptation.', 'is_active' => true]
        );

        $brandLeafCraft = Brand::firstOrCreate(
            ['slug' => 'leafcraft-pottery'],
            ['name' => 'LeafCraft Pottery', 'description' => 'Hand-thrown terracotta and rustic earthenware designed for botanical health.', 'is_active' => true]
        );

        $brandBioFlora = Brand::firstOrCreate(
            ['slug' => 'bioflora-organics'],
            ['name' => 'BioFlora Organics', 'description' => 'Zero-chemical microbial plant food and sustainable potting substrates.', 'is_active' => true]
        );

        // Inactive Brand (for testing)
        $brandInactive = Brand::firstOrCreate(
            ['slug' => 'inactive-brand'],
            ['name' => 'Inactive Demo Brand', 'description' => 'Should not appear anywhere on storefront.', 'is_active' => false]
        );

        // 3. Attributes
        $attrSize = Attribute::firstOrCreate(
            ['code' => 'pot-size'],
            ['name' => 'Pot Size', 'is_filterable' => true]
        );
        $valSmall = AttributeValue::firstOrCreate(
            ['attribute_id' => $attrSize->id, 'value' => 'small'],
            ['label' => 'Small (4 inch)', 'sort_order' => 1]
        );
        $valMedium = AttributeValue::firstOrCreate(
            ['attribute_id' => $attrSize->id, 'value' => 'medium'],
            ['label' => 'Medium (6 inch)', 'sort_order' => 2]
        );
        $valLarge = AttributeValue::firstOrCreate(
            ['attribute_id' => $attrSize->id, 'value' => 'large'],
            ['label' => 'Large (8 inch)', 'sort_order' => 3]
        );

        $attrLight = Attribute::firstOrCreate(
            ['code' => 'light-requirement'],
            ['name' => 'Light Requirement', 'is_filterable' => true]
        );
        $valLowLight = AttributeValue::firstOrCreate(
            ['attribute_id' => $attrLight->id, 'value' => 'low-light'],
            ['label' => 'Low Light Tolerant', 'sort_order' => 1]
        );
        $valBrightIndirect = AttributeValue::firstOrCreate(
            ['attribute_id' => $attrLight->id, 'value' => 'bright-indirect'],
            ['label' => 'Bright Indirect Light', 'sort_order' => 2]
        );

        // 4. Products

        // Product 1: Monstera Deliciosa (Multi-variant, In Stock, Featured)
        $p1 = Product::firstOrCreate(
            ['slug' => 'monstera-deliciosa'],
            [
                'name' => 'Monstera Deliciosa (Swiss Cheese Plant)',
                'base_sku' => 'MON-DEL-BASE',
                'brand_id' => $brandVerdant->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Iconic split-leaf tropical houseplant, vigorous and air-purifying.',
                'full_description' => '<p>The Swiss Cheese plant is a classic statement specimen known for its dramatic fenestrated leaves. Grows best in bright, indirect sunlight with balanced weekly watering.</p>',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
        $p1->categories()->syncWithoutDetaching([
            $indoor->id => ['is_primary' => true],
            $foliage->id => ['is_primary' => false]
        ]);

        if (File::exists($sampleTarget)) {
            ProductImage::firstOrCreate(
                ['product_id' => $p1->id, 'file_path' => 'products/sample-plant.jpg'],
                ['alt_text' => 'Lush Monstera Deliciosa foliage', 'sort_order' => 1, 'is_primary' => true]
            );
        }

        // Variant 1A: Small
        $v1a = ProductVariant::firstOrCreate(
            ['sku' => 'MON-DEL-SM'],
            [
                'product_id' => $p1->id,
                'price' => 499.00,
                'compare_at_price' => 599.00,
                'cost_price' => 250.00,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        $v1a->attributeValues()->syncWithoutDetaching([$valSmall->id, $valBrightIndirect->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v1a->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 20, 'reserved_quantity' => 0, 'safety_stock' => 2]
        );

        // Variant 1B: Medium
        $v1b = ProductVariant::firstOrCreate(
            ['sku' => 'MON-DEL-MD'],
            [
                'product_id' => $p1->id,
                'price' => 799.00,
                'compare_at_price' => 999.00,
                'cost_price' => 400.00,
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $v1b->attributeValues()->syncWithoutDetaching([$valMedium->id, $valBrightIndirect->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v1b->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 15, 'reserved_quantity' => 0, 'safety_stock' => 1]
        );

        // Variant 1C: Large (Out of Stock Variant for Variant Matrix testing)
        $v1c = ProductVariant::firstOrCreate(
            ['sku' => 'MON-DEL-LG'],
            [
                'product_id' => $p1->id,
                'price' => 1299.00,
                'compare_at_price' => 1499.00,
                'cost_price' => 600.00,
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $v1c->attributeValues()->syncWithoutDetaching([$valLarge->id, $valBrightIndirect->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v1c->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 0, 'reserved_quantity' => 0, 'safety_stock' => 0]
        );

        // Product 2: Snake Plant Laurentii (Air Purifying, Featured, Low Light)
        $p2 = Product::firstOrCreate(
            ['slug' => 'snake-plant-laurentii'],
            [
                'name' => 'Sansevieria Snake Plant Laurentii',
                'base_sku' => 'SNK-LAU-BASE',
                'brand_id' => $brandTerra->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Architectural sword-like leaves with yellow margins. Highly forgiving and low light tolerant.',
                'full_description' => '<p>One of the most resilient indoor plants available, thriving on neglect and drought tolerant.</p>',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
        $p2->categories()->syncWithoutDetaching([
            $indoor->id => ['is_primary' => true],
            $airPurifying->id => ['is_primary' => false]
        ]);

        $v2 = ProductVariant::firstOrCreate(
            ['sku' => 'SNK-LAU-MD'],
            [
                'product_id' => $p2->id,
                'price' => 349.00,
                'compare_at_price' => 449.00,
                'cost_price' => 150.00,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        $v2->attributeValues()->syncWithoutDetaching([$valMedium->id, $valLowLight->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v2->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 30, 'reserved_quantity' => 0, 'safety_stock' => 5]
        );

        // Product 3: Peace Lily Sweet Chico (Low Light, Flowering)
        $p3 = Product::firstOrCreate(
            ['slug' => 'peace-lily-sweet-chico'],
            [
                'name' => 'Peace Lily (Spathiphyllum) Sweet Chico',
                'base_sku' => 'PCE-LIL-BASE',
                'brand_id' => $brandTerra->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Glossy dark green leaves crowned with graceful white flower spathes.',
                'full_description' => '<p>Signals when thirsty with gentle leaf drooping, recovering rapidly upon bottom watering.</p>',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
        $p3->categories()->syncWithoutDetaching([
            $indoor->id => ['is_primary' => true],
            $flowering->id => ['is_primary' => false]
        ]);

        $v3 = ProductVariant::firstOrCreate(
            ['sku' => 'PCE-LIL-SM'],
            [
                'product_id' => $p3->id,
                'price' => 399.00,
                'compare_at_price' => 499.00,
                'cost_price' => 180.00,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        $v3->attributeValues()->syncWithoutDetaching([$valSmall->id, $valLowLight->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v3->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 18, 'reserved_quantity' => 0, 'safety_stock' => 2]
        );

        // Product 4: Handcrafted Terracotta Planter (Planters Category, Featured)
        $p4 = Product::firstOrCreate(
            ['slug' => 'handcrafted-terracotta-planter'],
            [
                'name' => 'Artisanal Ribbed Terracotta Planter',
                'base_sku' => 'POT-TER-BASE',
                'brand_id' => $brandLeafCraft->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Naturally breathable terracotta with bottom drainage hole and saucer.',
                'full_description' => '<p>Crafted by Indian potters using natural red river clay for maximum aeration and root health.</p>',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
        $p4->categories()->syncWithoutDetaching([
            $planters->id => ['is_primary' => true],
            $terracottaCategory->id => ['is_primary' => false]
        ]);

        $v4 = ProductVariant::firstOrCreate(
            ['sku' => 'POT-TER-MD'],
            [
                'product_id' => $p4->id,
                'price' => 299.00,
                'compare_at_price' => null,
                'cost_price' => 120.00,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        $v4->attributeValues()->syncWithoutDetaching([$valMedium->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v4->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 45, 'reserved_quantity' => 0, 'safety_stock' => 5]
        );

        // Product 5: Organic Bio-Compost 5kg (Low-stock for testing, Plant Care)
        $p5 = Product::firstOrCreate(
            ['slug' => 'organic-bio-compost-5kg'],
            [
                'name' => 'Microbial Enriched Bio-Compost (5kg)',
                'base_sku' => 'SOIL-BIO-5KG',
                'brand_id' => $brandBioFlora->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Aged vermicompost enriched with neem cake, seaweed extract, and trichoderma.',
                'full_description' => '<p>100% natural, odorless soil conditioner that strengthens mycorrhizal networks.</p>',
                'is_active' => true,
                'is_featured' => false,
            ]
        );
        $p5->categories()->syncWithoutDetaching([
            $plantCare->id => ['is_primary' => true]
        ]);

        $v5 = ProductVariant::firstOrCreate(
            ['sku' => 'SOIL-BIO-5KG-VAR'],
            [
                'product_id' => $p5->id,
                'price' => 249.00,
                'compare_at_price' => 299.00,
                'cost_price' => 110.00,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        Inventory::updateOrCreate(
            ['product_variant_id' => $v5->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 3, 'reserved_quantity' => 0, 'safety_stock' => 1] // Low stock: only 3!
        );

        // Product 6: Completely Out of Stock Product (Sold Out testing)
        $p6 = Product::firstOrCreate(
            ['slug' => 'fiddle-leaf-fig-tree'],
            [
                'name' => 'Ficus Lyrata (Fiddle Leaf Fig Tree)',
                'base_sku' => 'FIC-LYR-BASE',
                'brand_id' => $brandVerdant->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Magnificent violin-shaped foliage for bright statement interior spaces.',
                'full_description' => '<p>Grown with sturdy central trunk and acclimatized to household humidity.</p>',
                'is_active' => true,
                'is_featured' => false,
            ]
        );
        $p6->categories()->syncWithoutDetaching([
            $indoor->id => ['is_primary' => true],
            $foliage->id => ['is_primary' => false]
        ]);

        $v6 = ProductVariant::firstOrCreate(
            ['sku' => 'FIC-LYR-LG'],
            [
                'product_id' => $p6->id,
                'price' => 1499.00,
                'compare_at_price' => 1799.00,
                'cost_price' => 700.00,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        $v6->attributeValues()->syncWithoutDetaching([$valLarge->id, $valBrightIndirect->id]);
        Inventory::updateOrCreate(
            ['product_variant_id' => $v6->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 0, 'reserved_quantity' => 0, 'safety_stock' => 0] // 0 stock
        );

        // Product 7: Inactive Product (Security & catalog visibility testing)
        $p7 = Product::firstOrCreate(
            ['slug' => 'hidden-draft-plant'],
            [
                'name' => 'Hidden Draft Specimen (Should Never Appear)',
                'base_sku' => 'HID-DRF-01',
                'brand_id' => $brandVerdant->id,
                'tax_class_id' => $taxClass->id,
                'short_description' => 'Draft product that must be strictly hidden from public storefront.',
                'full_description' => '<p>Draft</p>',
                'is_active' => false,
            ]
        );
        ProductVariant::firstOrCreate(
            ['sku' => 'HID-DRF-VAR'],
            ['product_id' => $p7->id, 'price' => 99.00, 'cost_price' => 50.00, 'is_active' => true, 'is_default' => true]
        );

        // 5. Test Customer & Address
        $customer = Customer::firstOrCreate(
            ['phone' => '+919811114365'],
            [
                'name' => 'Ratan Swarnkar',
                'email' => 'ratan.swarnkar@example.com',
                'is_active' => true,
            ]
        );

        CustomerAddress::firstOrCreate(
            ['customer_id' => $customer->id, 'address_line_1' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd'],
            [
                'recipient_name' => 'Ratan Swarnkar',
                'phone' => '+919811114365',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'postal_code' => '110082',
                'country' => 'India',
                'address_type' => 'home',
                'is_default' => true,
            ]
        );
    }
}
