<?php

use App\Enums\AttributeType;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\AdminRbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
    Storage::fake('public');
});

test('cannot manipulate variant of another product via nested route (IDOR protection)', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $variantOfProduct2 = ProductVariant::factory()->create(['product_id' => $product2->id]);

    // Attempting to edit variant2 through product1 route must return 404
    $response = $this->get(route('admin.products.variants.edit', [$product1, $variantOfProduct2]));
    $response->assertNotFound();

    // Attempting to update variant2 through product1 route must return 404
    $updateResponse = $this->put(route('admin.products.variants.update', [$product1, $variantOfProduct2]), [
        'sku' => 'ATTACK-SKU',
        'price' => '100.00',
    ]);
    $updateResponse->assertNotFound();

    // Attempting to delete variant2 through product1 route must return 404
    $deleteResponse = $this->delete(route('admin.products.variants.destroy', [$product1, $variantOfProduct2]));
    $deleteResponse->assertNotFound();
});

test('cannot associate an image with a variant belonging to another product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $variantOfProduct2 = ProductVariant::factory()->create(['product_id' => $product2->id]);
    $image = createFakeImage('test.jpg');

    $response = $this->post(route('admin.products.images.store', $product1), [
        'images' => [$image],
        'product_variant_id' => $variantOfProduct2->id, // Belongs to product 2!
    ]);

    $response->assertSessionHasErrors('product_variant_id');
});

test('cannot set-primary or delete image belonging to another product (IDOR protection)', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $imageOfProduct2 = ProductImage::factory()->create(['product_id' => $product2->id]);

    $responseSetPrimary = $this->post(route('admin.products.images.set-primary', [$product1, $imageOfProduct2]));
    $responseSetPrimary->assertNotFound();

    $responseDelete = $this->delete(route('admin.products.images.destroy', [$product1, $imageOfProduct2]));
    $responseDelete->assertNotFound();
});

test('cannot reorder images using IDs from another product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $img1 = ProductImage::factory()->create(['product_id' => $product1->id]);
    $imgOfProduct2 = ProductImage::factory()->create(['product_id' => $product2->id]);

    $response = $this->post(route('admin.products.images.reorder', $product1), [
        'image_ids' => [$img1->id, $imgOfProduct2->id],
    ]);

    $response->assertSessionHasErrors('image_ids');
});

test('cannot update or delete attribute value through an unrelated attribute route (IDOR protection)', function () {
    $attribute1 = Attribute::factory()->create(['type' => AttributeType::SELECT]);
    $attribute2 = Attribute::factory()->create(['type' => AttributeType::SELECT]);

    $valueOfAttr2 = AttributeValue::factory()->create(['attribute_id' => $attribute2->id]);

    $response = $this->put(route('admin.attributes.values.update', [$attribute1, $valueOfAttr2]), [
        'label' => 'Hacked Label',
        'value' => 'hacked_val',
    ]);
    $response->assertNotFound();

    $deleteResponse = $this->delete(route('admin.attributes.values.destroy', [$attribute1, $valueOfAttr2]));
    $deleteResponse->assertNotFound();
});

test('cannot attach multiple values from the same attribute dimension to a variant', function () {
    $product = Product::factory()->create();
    $colorAttribute = Attribute::factory()->create(['name' => 'Color', 'type' => AttributeType::SELECT]);

    $red = AttributeValue::factory()->create(['attribute_id' => $colorAttribute->id, 'value' => 'red', 'label' => 'Red']);
    $blue = AttributeValue::factory()->create(['attribute_id' => $colorAttribute->id, 'value' => 'blue', 'label' => 'Blue']);

    // A variant cannot be simultaneously Red and Blue
    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'MULTI-DIM-001',
        'price' => '299.00',
        'attribute_value_ids' => [$red->id, $blue->id],
    ]);

    $response->assertSessionHasErrors('attribute_value_ids');
});

test('cannot attach values from boolean, text, or numeric attributes to variant pivot', function () {
    $product = Product::factory()->create();
    $textAttribute = Attribute::factory()->create(['name' => 'Botanical Name', 'type' => AttributeType::TEXT]);

    $textValue = AttributeValue::factory()->create(['attribute_id' => $textAttribute->id, 'value' => 'ficus']);

    $response = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'INVALID-TYPE-VAR',
        'price' => '299.00',
        'attribute_value_ids' => [$textValue->id],
    ]);

    $response->assertSessionHasErrors('attribute_value_ids');
});

test('cannot create duplicate variant attribute combinations on the same product', function () {
    $product = Product::factory()->create();
    $sizeAttribute = Attribute::factory()->create(['type' => AttributeType::SELECT]);
    $small = AttributeValue::factory()->create(['attribute_id' => $sizeAttribute->id, 'value' => 'sm']);

    // First variant with Small
    $response1 = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'DUPLICATE-CHECK-1',
        'price' => '200.00',
        'attribute_value_ids' => [$small->id],
    ]);
    $response1->assertRedirect();

    // Second variant with identical attribute combination must be rejected
    $response2 = $this->post(route('admin.products.variants.store', $product), [
        'sku' => 'DUPLICATE-CHECK-2',
        'price' => '250.00',
        'attribute_value_ids' => [$small->id],
    ]);
    $response2->assertSessionHasErrors('attribute_value_ids');
});

test('disguised executable files and SVG files are rejected during image upload', function () {
    $product = Product::factory()->create();

    // Disguised php file
    $fakePhpImage = UploadedFile::fake()->create('malicious.php.jpg', 50, 'text/plain');

    $responsePhp = $this->post(route('admin.products.images.store', $product), [
        'images' => [$fakePhpImage],
    ]);
    $responsePhp->assertSessionHasErrors();

    // SVG upload
    $svgFile = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

    $responseSvg = $this->post(route('admin.products.images.store', $product), [
        'images' => [$svgFile],
    ]);
    $responseSvg->assertSessionHasErrors();
});
