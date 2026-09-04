<?php

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Product;
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

test('brand index lists registered brands', function () {
    Brand::factory()->create(['name' => 'GreenFlora']);

    $response = $this->get(route('admin.brands.index'));
    $response->assertOk();
    $response->assertSee('GreenFlora');
});

test('brand can be created with logo upload and SEO metadata', function () {
    $logo = createFakeImage('logo.jpg');

    $response = $this->post(route('admin.brands.store'), [
        'name' => 'RootOrganic',
        'slug' => 'root-organic',
        'website' => 'https://rootorganic.example.com',
        'description' => 'Organic fertilizers and soils',
        'logo' => $logo,
        'is_active' => '1',
        'meta_title' => 'RootOrganic Soils & Fertilizers',
        'meta_description' => 'Premium certified organic gardening supplies.',
    ]);

    $response->assertRedirect(route('admin.brands.index'));
    $response->assertSessionHas('success');

    $brand = Brand::where('slug', 'root-organic')->first();
    expect($brand)->not->toBeNull()
        ->and($brand->logo_path)->not->toBeNull()
        ->and($brand->seoMetadata)->not->toBeNull()
        ->and($brand->seoMetadata->meta_title)->toBe('RootOrganic Soils & Fertilizers');

    Storage::disk('public')->assertExists($brand->logo_path);
});

test('updating brand logo deletes previous logo file from disk', function () {
    $oldLogo = createFakeImage('old_logo.png');
    $oldPath = $oldLogo->store('brands', 'public');

    $brand = Brand::factory()->create(['logo_path' => $oldPath]);
    Storage::disk('public')->assertExists($oldPath);

    $newLogo = createFakeImage('new_logo.webp');

    $response = $this->put(route('admin.brands.update', $brand), [
        'name' => $brand->name,
        'slug' => $brand->slug,
        'logo' => $newLogo,
    ]);

    $response->assertRedirect(route('admin.brands.index'));

    $brand->refresh();
    expect($brand->logo_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($brand->logo_path);
});

test('cannot delete brand associated with products', function () {
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);

    $response = $this->delete(route('admin.brands.destroy', $brand));
    $response->assertRedirect(route('admin.brands.index'));
    $response->assertSessionHas('error');

    expect(Brand::find($brand->id))->not->toBeNull();
});

test('clean brand can be deleted and purges logo from storage', function () {
    $logo = createFakeImage('logo.jpg');
    $logoPath = $logo->store('brands', 'public');

    $brand = Brand::factory()->create(['logo_path' => $logoPath]);
    Storage::disk('public')->assertExists($logoPath);

    $response = $this->delete(route('admin.brands.destroy', $brand));
    $response->assertRedirect(route('admin.brands.index'));
    $response->assertSessionHas('success');

    expect(Brand::find($brand->id))->toBeNull();
    Storage::disk('public')->assertMissing($logoPath);
});
