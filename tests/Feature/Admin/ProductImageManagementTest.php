<?php

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
    Storage::fake('public');
});

test('images can be uploaded and first image becomes primary', function () {
    $product = Product::factory()->create();
    $img1 = createFakeImage('leaf1.jpg');
    $img2 = createFakeImage('leaf2.png');

    $response = $this->post(route('admin.products.images.store', $product), [
        'images' => [$img1, $img2],
    ]);

    $response->assertRedirect(route('admin.products.images.index', $product));

    $images = $product->images()->get();
    expect($images)->toHaveCount(2);

    $primary = $images->firstWhere('is_primary', true);
    expect($primary)->not->toBeNull()
        ->and($images->where('is_primary', true)->count())->toBe(1);

    Storage::disk('public')->assertExists($primary->file_path);
});

test('setting an image as primary unsets all other primary images for that product', function () {
    $product = Product::factory()->create();
    $img1 = ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);
    $img2 = ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => false]);

    $response = $this->post(route('admin.products.images.set-primary', [$product, $img2]));
    $response->assertRedirect();

    expect($img2->fresh()->is_primary)->toBeTrue()
        ->and($img1->fresh()->is_primary)->toBeFalse();
});

test('deleting primary image promotes the earliest remaining image to primary', function () {
    $product = Product::factory()->create();
    $img1 = ProductImage::factory()->create([
        'product_id' => $product->id,
        'is_primary' => true,
        'sort_order' => 1,
    ]);
    $img2 = ProductImage::factory()->create([
        'product_id' => $product->id,
        'is_primary' => false,
        'sort_order' => 2,
    ]);

    $response = $this->delete(route('admin.products.images.destroy', [$product, $img1]));
    $response->assertRedirect(route('admin.products.images.index', $product));

    expect(ProductImage::find($img1->id))->toBeNull()
        ->and($img2->fresh()->is_primary)->toBeTrue();
});

test('images can be reordered successfully', function () {
    $product = Product::factory()->create();
    $img1 = ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 1]);
    $img2 = ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 2]);

    $response = $this->post(route('admin.products.images.reorder', $product), [
        'image_ids' => [$img2->id, $img1->id],
    ]);

    $response->assertRedirect();

    expect($img2->fresh()->sort_order)->toBe(0)
        ->and($img1->fresh()->sort_order)->toBe(1);
});
