<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
});

test('category index loads categories with hierarchy', function () {
    $parent = Category::factory()->create(['name' => 'Indoor Plants']);
    Category::factory()->create(['name' => 'Air Purifying', 'parent_id' => $parent->id]);

    $response = $this->get(route('admin.categories.index'));
    $response->assertOk();
    $response->assertSee('Indoor Plants');
    $response->assertSee('Air Purifying');
});

test('category can be created with parent and SEO metadata', function () {
    $parent = Category::factory()->create(['name' => 'Gardening Tools']);

    $response = $this->post(route('admin.categories.store'), [
        'name' => 'Pruning Shears',
        'slug' => 'pruning-shears',
        'parent_id' => $parent->id,
        'description' => 'High quality sharp garden shears',
        'sort_order' => 1,
        'is_active' => '1',
        'meta_title' => 'Buy Pruning Shears Online',
        'meta_description' => 'Best pruning shears for indoor and outdoor plants.',
    ]);

    $response->assertRedirect(route('admin.categories.index'));
    $response->assertSessionHas('success');

    $category = Category::where('slug', 'pruning-shears')->first();
    expect($category)->not->toBeNull()
        ->and($category->parent_id)->toBe($parent->id)
        ->and($category->seoMetadata)->not->toBeNull()
        ->and($category->seoMetadata->meta_title)->toBe('Buy Pruning Shears Online');
});

test('category cannot be its own parent', function () {
    $category = Category::factory()->create();

    $response = $this->put(route('admin.categories.update', $category), [
        'name' => 'Updated Category',
        'parent_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
});

test('category cannot set its descendant as its parent (cycle prevention)', function () {
    $grandparent = Category::factory()->create(['name' => 'Plants']);
    $parent = Category::factory()->create(['name' => 'Indoor', 'parent_id' => $grandparent->id]);
    $child = Category::factory()->create(['name' => 'Succulents', 'parent_id' => $parent->id]);

    // Attempting to set child as grandparent's parent must fail
    $response = $this->put(route('admin.categories.update', $grandparent), [
        'name' => 'Plants',
        'parent_id' => $child->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
});

test('cannot delete category with subcategories', function () {
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $response = $this->delete(route('admin.categories.destroy', $parent));
    $response->assertRedirect(route('admin.categories.index'));
    $response->assertSessionHas('error');

    expect(Category::find($parent->id))->not->toBeNull();
});

test('cannot delete category with assigned products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id, ['is_primary' => true]);

    $response = $this->delete(route('admin.categories.destroy', $category));
    $response->assertRedirect(route('admin.categories.index'));
    $response->assertSessionHas('error');

    expect(Category::find($category->id))->not->toBeNull();
});

test('clean category can be deleted successfully', function () {
    $category = Category::factory()->create(['name' => 'Empty Category']);

    $response = $this->delete(route('admin.categories.destroy', $category));
    $response->assertRedirect(route('admin.categories.index'));
    $response->assertSessionHas('success');

    expect(Category::find($category->id))->toBeNull();
});
