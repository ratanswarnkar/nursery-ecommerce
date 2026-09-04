<?php

use App\Services\Auth\PhoneNumberNormalizer;

test('normalizes standard 10-digit Indian phone numbers to E.164', function () {
    $normalizer = new PhoneNumberNormalizer;

    expect($normalizer->normalize('9876543210'))->toBe('+919876543210')
        ->and($normalizer->normalize('09876543210'))->toBe('+919876543210')
        ->and($normalizer->normalize('+91 98765 43210'))->toBe('+919876543210')
        ->and($normalizer->normalize('+91-98765-43210'))->toBe('+919876543210');
});

test('normalizes valid international numbers', function () {
    $normalizer = new PhoneNumberNormalizer;

    expect($normalizer->normalize('+1 202 555 0123'))->toBe('+12025550123')
        ->and($normalizer->normalize('+44 7911 123456'))->toBe('+447911123456');
});

test('rejects malformed or invalid phone numbers', function () {
    $normalizer = new PhoneNumberNormalizer;

    expect(fn () => $normalizer->normalize('12345'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $normalizer->normalize('abcdefghij'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $normalizer->normalize(''))->toThrow(InvalidArgumentException::class);
});

test('isValid helper accurately checks phone validity', function () {
    $normalizer = new PhoneNumberNormalizer;

    expect($normalizer->isValid('9876543210'))->toBeTrue()
        ->and($normalizer->isValid('+919876543210'))->toBeTrue()
        ->and($normalizer->isValid('invalid-phone'))->toBeFalse()
        ->and($normalizer->isValid('12345'))->toBeFalse();
});
