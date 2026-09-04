<?php

use App\Enums\AddressType;
use App\Enums\AttributeType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Enums\ShippingStatus;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\PaymentWebhook;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\SeoMetadata;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\TaxRule;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;

test('1. customer and customer address relationships and default address flag work', function () {
    $customer = Customer::factory()->create([
        'phone' => '9876543210',
        'name' => 'John Doe',
    ]);

    $address1 = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'address_type' => AddressType::HOME,
        'is_default' => true,
    ]);

    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'address_type' => AddressType::WORK,
        'is_default' => false,
    ]);

    expect($customer->addresses)->toHaveCount(2)
        ->and($address1->customer->id)->toBe($customer->id)
        ->and($address1->address_type)->toBe(AddressType::HOME)
        ->and($address1->is_default)->toBeTrue()
        ->and($address2->address_type)->toBe(AddressType::WORK)
        ->and($address2->is_default)->toBeFalse();

    // Verify unique phone constraint
    expect(fn () => Customer::factory()->create(['phone' => '9876543210']))
        ->toThrow(QueryException::class);
});

test('2. category hierarchy supports parent-child recursive relationships', function () {
    $parent = Category::factory()->create([
        'name' => 'Indoor Plants',
        'parent_id' => null,
    ]);

    $child1 = Category::factory()->create([
        'name' => 'Air Purifying Plants',
        'parent_id' => $parent->id,
    ]);

    $child2 = Category::factory()->create([
        'name' => 'Bonsai',
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(2)
        ->and($child1->parent->id)->toBe($parent->id)
        ->and($child2->parent->name)->toBe('Indoor Plants');
});

test('3. product and category have many-to-many relationship with is_primary pivot', function () {
    $product = Product::factory()->create(['name' => 'Snake Plant']);
    $category1 = Category::factory()->create(['name' => 'Indoor']);
    $category2 = Category::factory()->create(['name' => 'Low Light']);

    $product->categories()->attach([
        $category1->id => ['is_primary' => true],
        $category2->id => ['is_primary' => false],
    ]);

    $productCategories = $product->fresh()->categories;
    expect($productCategories)->toHaveCount(2);

    $primaryCat = $productCategories->firstWhere('id', $category1->id);
    $secondaryCat = $productCategories->firstWhere('id', $category2->id);

    expect((bool) $primaryCat->pivot->is_primary)->toBeTrue()
        ->and((bool) $secondaryCat->pivot->is_primary)->toBeFalse();
});

test('4. product and product variant relationship and variant uniqueness', function () {
    $product = Product::factory()->create(['base_sku' => 'MONST-001']);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'MONST-001-SM',
        'price' => 399.00,
        'is_default' => true,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'MONST-001-LG',
        'price' => 799.00,
        'is_default' => false,
    ]);

    expect($product->variants)->toHaveCount(2)
        ->and($variant1->product->id)->toBe($product->id)
        ->and((float) $variant1->price)->toBe(399.00);

    // Verify SKU uniqueness
    expect(fn () => ProductVariant::factory()->create(['sku' => 'MONST-001-SM']))
        ->toThrow(QueryException::class);
});

test('5. dynamic attributes and variant attribute values pivot', function () {
    $attribute = Attribute::factory()->create([
        'name' => 'Pot Color',
        'code' => 'pot_color',
        'type' => AttributeType::SELECT,
    ]);

    $valueTerracotta = AttributeValue::factory()->create([
        'attribute_id' => $attribute->id,
        'value' => 'terracotta',
        'label' => 'Terracotta Red',
    ]);

    $valueWhite = AttributeValue::factory()->create([
        'attribute_id' => $attribute->id,
        'value' => 'white',
        'label' => 'Ceramic White',
    ]);

    $variant = ProductVariant::factory()->create();
    $variant->attributeValues()->attach($valueTerracotta->id);

    expect($variant->fresh()->attributeValues)->toHaveCount(1)
        ->and($variant->fresh()->attributeValues->first()->label)->toBe('Terracotta Red')
        ->and($attribute->values)->toHaveCount(2);
});

test('6. inventory uniqueness constraint per variant and warehouse', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();

    $inventory = Inventory::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 150,
        'reserved_quantity' => 10,
        'safety_stock' => 5,
    ]);

    expect($inventory->quantity)->toBe(150)
        ->and($inventory->productVariant->id)->toBe($variant->id)
        ->and($inventory->warehouse->id)->toBe($warehouse->id);

    // Duplicate (variant, warehouse) must throw unique constraint exception
    expect(fn () => Inventory::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 50,
    ]))->toThrow(QueryException::class);
});

test('7. stock reservation and movement models behave correctly', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();

    $movement = StockMovement::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'type' => StockMovementType::INBOUND,
        'quantity' => 50,
        'previous_quantity' => 0,
        'new_quantity' => 50,
        'notes' => 'Seasonal nursery shipment',
    ]);

    $reservation = StockReservation::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 3,
        'status' => StockReservationStatus::ACTIVE,
        'expires_at' => now()->addMinutes(15),
    ]);

    expect($movement->type)->toBe(StockMovementType::INBOUND)
        ->and($reservation->status)->toBe(StockReservationStatus::ACTIVE)
        ->and($reservation->quantity)->toBe(3);
});

test('8. order preserves historical address and item snapshot', function () {
    $customer = Customer::factory()->create();
    $variant = ProductVariant::factory()->create(['price' => 450.00]);

    $order = Order::create([
        'order_number' => 'ORD-TEST-001',
        'customer_id' => $customer->id,
        'status' => OrderStatus::PENDING,
        'payment_status' => PaymentStatus::PENDING,
        'shipping_status' => ShippingStatus::UNFULFILLED,
        'currency' => 'INR',
        'subtotal' => 450.00,
        'tax_amount' => 81.00,
        'shipping_amount' => 50.00,
        'discount_amount' => 0.00,
        'grand_total' => 581.00,
        'shipping_address_json' => [
            'recipient_name' => 'Alice Green',
            'phone' => '9988776655',
            'address_line_1' => '12 Garden Lane',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country' => 'India',
        ],
        'billing_address_json' => [
            'recipient_name' => 'Alice Green',
            'phone' => '9988776655',
            'address_line_1' => '12 Garden Lane',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country' => 'India',
        ],
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => 'Areca Palm',
        'variant_name' => 'Medium (10 inch pot)',
        'sku' => 'ARC-PLM-MD',
        'price' => 450.00,
        'quantity' => 1,
        'subtotal' => 450.00,
        'tax_amount' => 81.00,
        'discount_amount' => 0.00,
        'total' => 531.00,
    ]);

    expect($order->shipping_address_json['recipient_name'])->toBe('Alice Green')
        ->and($orderItem->product_name)->toBe('Areca Palm')
        ->and($orderItem->sku)->toBe('ARC-PLM-MD')
        ->and($order->items)->toHaveCount(1);
});

test('9. payment webhook idempotency constraint prevents duplicate processing', function () {
    PaymentWebhook::create([
        'gateway' => 'razorpay',
        'event_id' => 'evt_1234567890',
        'event_type' => 'payment.captured',
        'payload' => ['id' => 'evt_1234567890', 'event' => 'payment.captured'],
        'is_processed' => true,
        'processed_at' => now(),
    ]);

    // Replay of same event_id for same gateway must fail unique constraint
    expect(fn () => PaymentWebhook::create([
        'gateway' => 'razorpay',
        'event_id' => 'evt_1234567890',
        'event_type' => 'payment.captured',
        'payload' => ['id' => 'evt_1234567890'],
    ]))->toThrow(QueryException::class);
});

test('10. tax class, tax rates, and tax rules map correctly', function () {
    $taxClass = TaxClass::factory()->create(['name' => 'Plants GST']);
    $taxRate = TaxRate::factory()->create(['name' => 'GST 18%', 'rate' => 18.00]);

    $taxRule = TaxRule::create([
        'tax_class_id' => $taxClass->id,
        'tax_rate_id' => $taxRate->id,
        'country' => 'IN',
        'priority' => 1,
        'is_active' => true,
    ]);

    expect($taxRule->taxClass->id)->toBe($taxClass->id)
        ->and($taxRule->taxRate->id)->toBe($taxRate->id)
        ->and((float) $taxRate->rate)->toBe(18.00);
});

test('11. product reviews support moderation statuses', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create();

    $review = ProductReview::create([
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'rating' => 5,
        'title' => 'Stunning foliage',
        'comment' => 'Exceeded my expectations!',
        'status' => ReviewStatus::PENDING,
    ]);

    expect($review->status)->toBe(ReviewStatus::PENDING);

    $review->update(['status' => ReviewStatus::APPROVED, 'moderated_at' => now()]);
    expect($review->fresh()->status)->toBe(ReviewStatus::APPROVED);
});

test('12. SEO polymorphic relationships attach to Product, Category, Brand, Page, and BlogPost', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $page = Page::factory()->create();
    $blog = BlogPost::factory()->create();

    $seoProduct = SeoMetadata::create([
        'seoable_type' => Product::class,
        'seoable_id' => $product->id,
        'meta_title' => 'Buy Snake Plant Online',
        'meta_description' => 'Best air purifying snake plant for home and office.',
    ]);

    $seoCategory = SeoMetadata::create([
        'seoable_type' => Category::class,
        'seoable_id' => $category->id,
        'meta_title' => 'Indoor Plants Collection',
    ]);

    $seoBrand = SeoMetadata::create([
        'seoable_type' => Brand::class,
        'seoable_id' => $brand->id,
        'meta_title' => 'GreenOasis Brand',
    ]);

    $seoPage = SeoMetadata::create([
        'seoable_type' => Page::class,
        'seoable_id' => $page->id,
        'meta_title' => 'About Our Nursery',
    ]);

    $seoBlog = SeoMetadata::create([
        'seoable_type' => BlogPost::class,
        'seoable_id' => $blog->id,
        'meta_title' => 'Top 10 Indoor Gardening Tips',
    ]);

    expect($product->fresh()->seoMetadata->meta_title)->toBe('Buy Snake Plant Online')
        ->and($category->fresh()->seoMetadata->meta_title)->toBe('Indoor Plants Collection')
        ->and($brand->fresh()->seoMetadata->meta_title)->toBe('GreenOasis Brand')
        ->and($page->fresh()->seoMetadata->meta_title)->toBe('About Our Nursery')
        ->and($blog->fresh()->seoMetadata->meta_title)->toBe('Top 10 Indoor Gardening Tips');
});
