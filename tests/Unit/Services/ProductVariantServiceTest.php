<?php

use App\Services\Catalog\ProductVariantService;
use Illuminate\Validation\ValidationException;

test('validateMoneyRules accepts valid prices and compare_at_price >= price', function () {
    $service = new ProductVariantService;

    // Normal valid case
    $service->validateMoneyRules('499.00', '699.00', '250.00');
    expect(true)->toBeTrue();

    // Equal compare_at and price
    $service->validateMoneyRules('499.00', '499.00', null);
    expect(true)->toBeTrue();
});

test('validateMoneyRules throws ValidationException if compare_at is less than price', function () {
    $service = new ProductVariantService;

    expect(fn () => $service->validateMoneyRules('500.00', '400.00', null))
        ->toThrow(ValidationException::class);
});

test('validateMoneyRules throws ValidationException for negative prices', function () {
    $service = new ProductVariantService;

    expect(fn () => $service->validateMoneyRules('-50.00', null, null))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->validateMoneyRules('50.00', '-10.00', null))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->validateMoneyRules('50.00', '60.00', '-5.00'))
        ->toThrow(ValidationException::class);
});

test('validateMoneyRules accepts boundary values including zero, minimum penny, and upper limit', function () {
    $service = new ProductVariantService;

    // Boundary: 0
    $service->validateMoneyRules('0.00', '0.00', '0.00');
    expect(true)->toBeTrue();

    // Boundary: 0.01
    $service->validateMoneyRules('0.01', '0.01', '0.00');
    expect(true)->toBeTrue();

    // Boundary: 999999999.99
    $service->validateMoneyRules('999999999.99', '999999999.99', '500000000.00');
    expect(true)->toBeTrue();

    // cost_price may exceed selling price (promotional/loss-leader scenario)
    $service->validateMoneyRules('100.00', '200.00', '150.00');
    expect(true)->toBeTrue();
});
