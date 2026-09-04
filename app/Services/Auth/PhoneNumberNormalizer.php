<?php

namespace App\Services\Auth;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberNormalizer
{
    private PhoneNumberUtil $phoneUtil;

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Normalize a phone number to strict E.164 representation.
     *
     * @throws InvalidArgumentException
     */
    public function normalize(string $phone, string $defaultRegion = 'IN'): string
    {
        $cleaned = trim($phone);

        if (empty($cleaned)) {
            throw new InvalidArgumentException('Phone number cannot be empty.');
        }

        try {
            $parsed = $this->phoneUtil->parse($cleaned, $defaultRegion);

            if (! $this->phoneUtil->isValidNumber($parsed)) {
                throw new InvalidArgumentException("The phone number [{$phone}] is not a valid mobile phone number.");
            }

            return $this->phoneUtil->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            throw new InvalidArgumentException("Failed to parse phone number [{$phone}]: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Determine if a phone number is valid for the given region.
     */
    public function isValid(string $phone, string $defaultRegion = 'IN'): bool
    {
        try {
            $this->normalize($phone, $defaultRegion);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
