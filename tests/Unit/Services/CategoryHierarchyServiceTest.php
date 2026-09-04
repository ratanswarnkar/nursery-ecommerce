<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryHierarchyService;

test('isDescendant detects direct and deep hierarchical descendants', function () {
    $service = new CategoryHierarchyService;

    $level1 = Category::factory()->create();
    $level2 = Category::factory()->create(['parent_id' => $level1->id]);
    $level3 = Category::factory()->create(['parent_id' => $level2->id]);
    $unrelated = Category::factory()->create();

    // level2 is descendant of level1
    expect($service->isDescendant($level2->id, $level1->id))->toBeTrue();

    // level3 is descendant of level1
    expect($service->isDescendant($level3->id, $level1->id))->toBeTrue();

    // level1 is NOT descendant of level3
    expect($service->isDescendant($level1->id, $level3->id))->toBeFalse();

    // unrelated is NOT descendant of level1
    expect($service->isDescendant($unrelated->id, $level1->id))->toBeFalse();

    // self is considered descendant/cycle
    expect($service->isDescendant($level1->id, $level1->id))->toBeTrue();
});

test('canDelete returns false if children or products exist', function () {
    $service = new CategoryHierarchyService;

    $categoryWithChild = Category::factory()->create();
    Category::factory()->create(['parent_id' => $categoryWithChild->id]);

    expect($service->canDelete($categoryWithChild)['allowed'])->toBeFalse();

    $categoryWithProduct = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($categoryWithProduct->id);

    expect($service->canDelete($categoryWithProduct)['allowed'])->toBeFalse();

    $cleanCategory = Category::factory()->create();
    expect($service->canDelete($cleanCategory)['allowed'])->toBeTrue();
});
