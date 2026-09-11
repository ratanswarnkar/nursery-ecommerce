<?php

use App\Enums\AddressType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('customer-checkout');
    RateLimiter::clear('payment-verify');
    RateLimiter::clear('payment-cancel');

    $this->warehouse = Warehouse::create([
        'name' => 'Main Delhi Hub',
        'code' => 'DEL-HUB-01',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->customer = Customer::factory()->create([
        'name' => 'Pooja Verma',
        'phone' => '+919811199988',
        'email' => 'pooja@example.com',
        'auth_token_version' => 1,
        'is_active' => true,
    ]);

    $this->address = CustomerAddress::create([
        'customer_id' => $this->customer->id,
        'address_type' => AddressType::HOME,
        'recipient_name' => 'Pooja Verma',
        'phone' => '+919811199988',
        'address_line_1' => 'Mann Enclave, near Gurukul',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110082',
        'country' => 'India',
        'is_default' => true,
    ]);

    $this->product = Product::factory()->create([
        'name' => 'Peace Lily',
        'is_active' => true,
    ]);

    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'PL-001',
        'price' => 500.00,
        'is_active' => true,
    ]);

    $this->inventory = Inventory::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 25,
        'reserved_quantity' => 0,
        'safety_stock' => 2,
    ]);
});

/*
|--------------------------------------------------------------------------
| Production Readiness Tests
|--------------------------------------------------------------------------
*/

test('1. production asset integration resolves vite assets when manifest exists', function () {
    expect(file_exists(public_path('build/manifest.json')))->toBeTrue();

    $vite = app(Vite::class);
    $html = (string) $vite(['resources/css/app.css', 'resources/js/app.js']);

    expect($html)->toContain('/build/assets/app.css');
    expect($html)->toContain('/build/assets/app.js');
});

test('2. timezone configuration is Asia/Kolkata', function () {
    expect(config('app.timezone'))->toBe('Asia/Kolkata');
    expect(now()->getTimezone()->getName())->toBe('Asia/Kolkata');
});

test('3. config cache succeeds and configuration is resolved cleanly', function () {
    Artisan::call('config:clear');
    $code = Artisan::call('config:cache');
    expect($code)->toBe(0);

    expect(config('app.timezone'))->toBe('Asia/Kolkata');
    expect(config('ecommerce.shipping.flat_rate'))->toBe('0.00');

    Artisan::call('config:clear');
});

test('4. branded 404 page renders with botanical theme and recovery actions', function () {
    $response = $this->get('/non-existent-page-random-404');
    $response->assertStatus(404);
    $response->assertSee('Error 404');
    $response->assertSee('Plant or Page Not Found');
    $response->assertSee('Return to Homepage');
    $response->assertSee('Sugandha Farms and Nursery');
});

test('5. branded 403 page renders with access restricted message', function () {
    $html = view('errors.403')->render();
    expect($html)->toContain('Error 403');
    expect($html)->toContain('Access Restricted');
    expect($html)->toContain('Sugandha Farms and Nursery');
});

test('6. branded 419 page renders with session expired message and refresh action', function () {
    $html = view('errors.419')->render();
    expect($html)->toContain('Error 419');
    expect($html)->toContain('Session Expired');
    expect($html)->toContain('Refresh Page');
});

test('7. branded 429 page renders with rate limit message', function () {
    $html = view('errors.429')->render();
    expect($html)->toContain('Error 429');
    expect($html)->toContain('Too Many Requests');
    expect($html)->toContain('Sugandha Farms and Nursery');
});

test('8. branded 500 page renders with botanical server error message', function () {
    $html = view('errors.500')->render();
    expect($html)->toContain('Error 500');
    expect($html)->toContain('Botanical System Hiccup');
    expect($html)->toContain('Sugandha Farms and Nursery');
});

test('9. branded 503 page renders with maintenance mode notice', function () {
    $html = view('errors.503')->render();
    expect($html)->toContain('Maintenance Mode');
    expect($html)->toContain('Nursery Under Tending');
    expect($html)->toContain('Sugandha Farms and Nursery');
});

test('10. production security headers are attached to responses', function () {
    $response = $this->get('/');
    $response->assertOk();

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain('checkout.razorpay.com');
    expect($csp)->toContain('fonts.googleapis.com');
});

test('11. HSTS header is attached when request is secure', function () {
    $response = $this->get('https://localhost/');
    $response->assertOk();
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('12. robots.txt disallows sensitive paths and points to sitemap', function () {
    $content = file_get_contents(public_path('robots.txt'));
    expect($content)->toContain('Disallow: /checkout');
    expect($content)->toContain('Disallow: /admin');
    expect($content)->toContain('Disallow: /account');
    expect($content)->toContain('Sitemap: /sitemap.xml');
});

test('13. sitemap.xml returns valid public sitemap and excludes private paths', function () {
    $response = $this->get(route('sitemap'));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    $content = $response->getContent();
    expect($content)->toContain('<urlset');
    expect($content)->toContain(route('home'));
    expect($content)->toContain(route('shop.index'));
    expect($content)->not->toContain('/admin');
    expect($content)->not->toContain('/checkout');
    expect($content)->not->toContain('/account');
});

test('14. existing storefront homepage and catalog render cleanly', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('shop.index'))->assertOk();
    $this->get(route('products.show', $this->product->slug))->assertOk();
});

test('15. existing authenticated checkout renders cleanly with business rules', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    $response = $this->get(route('checkout.index'));
    $response->assertOk();
    $response->assertSee('Select Delivery Address');
    $response->assertSee('Delivery available only within Delhi NCR.');
    $response->assertSee('Delivery is expected within 3 days.');
    $response->assertSee('Orders ABOVE ₹1,000 qualify for FREE delivery.');
});

test('16. customer OTP request endpoint remains functional', function () {
    $response = $this->from(route('customer.login'))->post(route('customer.otp.request'), [
        'phone' => '+919811199988',
    ]);
    $response->assertRedirect(route('customer.login'));
    $response->assertSessionHas('otp_requested', true);
});

test('17. Phase 9-A rate limiting remains active on sensitive checkout route', function () {
    $this->actingAs($this->customer, 'customer');
    session(['customer_auth_token_version' => $this->customer->auth_token_version]);

    $cart = Cart::create(['customer_id' => $this->customer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 1,
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $this->post(route('checkout.store'), [
            'shipping_address_id' => $this->address->id,
            'billing_same_as_shipping' => 1,
            'idempotency_key' => "test-p9b-rl-{$i}",
        ]);

        if (! $cart->fresh()->items()->exists()) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
            ]);
        }
    }

    $response6 = $this->post(route('checkout.store'), [
        'shipping_address_id' => $this->address->id,
        'billing_same_as_shipping' => 1,
        'idempotency_key' => 'test-p9b-rl-6',
    ]);

    $response6->assertStatus(429);
});
