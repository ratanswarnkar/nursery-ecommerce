<?php

use App\Enums\AttributeType;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use Database\Seeders\AdminRbacSeeder;

beforeEach(function () {
    $this->seed(AdminRbacSeeder::class);
    $this->admin = Admin::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->actingAs($this->admin, 'admin');
    session(['admin_auth_token_version' => $this->admin->auth_token_version]);
});

test('attribute index lists configured attributes', function () {
    Attribute::factory()->create(['name' => 'Pot Size', 'code' => 'pot_size']);

    $response = $this->get(route('admin.attributes.index'));
    $response->assertOk();
    $response->assertSee('Pot Size');
    $response->assertSee('pot_size');
});

test('attribute can be created with valid code and enum type', function () {
    $response = $this->post(route('admin.attributes.store'), [
        'name' => 'Planter Material',
        'code' => 'planter_material',
        'type' => AttributeType::SELECT->value,
        'is_required' => '1',
        'is_filterable' => '1',
        'sort_order' => 2,
    ]);

    $response->assertRedirect(route('admin.attributes.index'));
    $response->assertSessionHas('success');

    $attribute = Attribute::where('code', 'planter_material')->first();
    expect($attribute)->not->toBeNull()
        ->and($attribute->type)->toBe(AttributeType::SELECT)
        ->and($attribute->is_required)->toBeTrue();
});

test('attribute values can be managed for an attribute', function () {
    $attribute = Attribute::factory()->create(['type' => AttributeType::SELECT]);

    // Create value
    $response = $this->post(route('admin.attributes.values.store', $attribute), [
        'label' => 'Ceramic Glazed',
        'value' => 'ceramic_glazed',
        'sort_order' => 1,
    ]);

    $response->assertRedirect(route('admin.attributes.values.index', $attribute));
    $response->assertSessionHas('success');

    $val = $attribute->values()->where('value', 'ceramic_glazed')->first();
    expect($val)->not->toBeNull()
        ->and($val->label)->toBe('Ceramic Glazed');

    // Update value
    $updateResponse = $this->put(route('admin.attributes.values.update', [$attribute, $val]), [
        'label' => 'Premium Ceramic Glazed',
        'value' => 'ceramic_glazed',
        'sort_order' => 2,
    ]);
    $updateResponse->assertRedirect(route('admin.attributes.values.index', $attribute));
    expect($val->fresh()->label)->toBe('Premium Ceramic Glazed');
});

test('cannot delete attribute if its values are assigned to variants', function () {
    $attribute = Attribute::factory()->create(['type' => AttributeType::SELECT]);
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);
    $variant = ProductVariant::factory()->create();
    $variant->attributeValues()->attach($value->id);

    $response = $this->delete(route('admin.attributes.destroy', $attribute));
    $response->assertRedirect(route('admin.attributes.index'));
    $response->assertSessionHas('error');

    expect(Attribute::find($attribute->id))->not->toBeNull();
});

test('cannot delete attribute value assigned to variants', function () {
    $attribute = Attribute::factory()->create(['type' => AttributeType::SELECT]);
    $value = AttributeValue::factory()->create(['attribute_id' => $attribute->id]);
    $variant = ProductVariant::factory()->create();
    $variant->attributeValues()->attach($value->id);

    $response = $this->delete(route('admin.attributes.values.destroy', [$attribute, $value]));
    $response->assertRedirect(route('admin.attributes.values.index', $attribute));
    $response->assertSessionHas('error');

    expect(AttributeValue::find($value->id))->not->toBeNull();
});
