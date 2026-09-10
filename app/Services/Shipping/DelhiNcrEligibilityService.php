<?php

namespace App\Services\Shipping;

use App\Models\CustomerAddress;

class DelhiNcrEligibilityService
{
    /**
     * Recognized NCT of Delhi administrative state names.
     *
     * @var array<string>
     */
    protected const DELHI_STATE_NAMES = [
        'delhi',
        'new delhi',
        'nct of delhi',
        'national capital territory of delhi',
        'dl',
    ];

    /**
     * Allowlist of recognized Haryana NCR core administrative localities and their India Post postal prefixes.
     * Restricts strictly to contiguous NCR urban centers rather than entire Haryana.
     *
     * @var array<string, string>
     */
    protected const HARYANA_NCR_LOCALITIES = [
        'gurugram' => '122',
        'gurgaon' => '122',
        'manesar' => '122',
        'sohna' => '122',
        'faridabad' => '121',
        'ballabhgarh' => '121',
        'ballabgarh' => '121',
    ];

    /**
     * Allowlist of recognized Uttar Pradesh NCR core administrative localities and their India Post postal prefixes.
     * Restricts strictly to contiguous NCR urban centers rather than entire Uttar Pradesh.
     *
     * @var array<string, string>
     */
    protected const UP_NCR_LOCALITIES = [
        'noida' => '2013',
        'greater noida' => '2013',
        'gautam buddha nagar' => '2013',
        'gautam budh nagar' => '2013',
        'ghaziabad' => '2010',
        'sahibabad' => '2010',
        'indirapuram' => '2010',
        'vaishali' => '2010',
    ];

    /**
     * Determine if an address (model instance or attribute array) is within the eligible Delhi NCR delivery zone.
     *
     * @param  CustomerAddress|array{state?: ?string, city?: ?string, postal_code?: ?string, country?: ?string}  $address
     */
    public function isEligible(CustomerAddress|array $address): bool
    {
        $state = trim((string) ($address instanceof CustomerAddress ? $address->state : ($address['state'] ?? '')));
        $city = trim((string) ($address instanceof CustomerAddress ? $address->city : ($address['city'] ?? '')));
        $pincode = trim((string) ($address instanceof CustomerAddress ? $address->postal_code : ($address['postal_code'] ?? '')));
        $country = trim((string) ($address instanceof CustomerAddress ? ($address->country ?? 'India') : ($address['country'] ?? 'India')));

        // 1. Country validation: must be India if explicitly specified
        $normalizedCountry = strtolower($country);
        if ($normalizedCountry !== '' && ! in_array($normalizedCountry, ['india', 'in', 'ind'], true)) {
            return false;
        }

        // 2. Format validation: must be a valid 6-digit Indian PIN code
        if (! preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
            return false;
        }

        $normalizedState = strtolower($state);
        $normalizedCity = strtolower($city);

        // 3. NCT of Delhi
        // All official NCT Delhi post offices use PIN codes starting with 11 (110001 - 110099).
        // Addresses claiming state = Delhi with a non-11 PIN code (e.g. 400001 Mumbai) are inconsistent and rejected.
        if (in_array($normalizedState, self::DELHI_STATE_NAMES, true)) {
            return str_starts_with($pincode, '11');
        }

        // 4. Haryana NCR core localities
        if (in_array($normalizedState, ['haryana', 'hr'], true)) {
            return $this->matchesHaryanaNcr($normalizedCity, $pincode);
        }

        // 5. Uttar Pradesh NCR core localities
        if (in_array($normalizedState, ['uttar pradesh', 'up'], true)) {
            return $this->matchesUpNcr($normalizedCity, $pincode);
        }

        // 6. Explicit 'Delhi NCR' / 'NCR' state label
        if (in_array($normalizedState, ['delhi ncr', 'ncr'], true)) {
            if (str_starts_with($pincode, '11')) {
                return true;
            }
            if ($this->matchesHaryanaNcr($normalizedCity, $pincode)) {
                return true;
            }
            if ($this->matchesUpNcr($normalizedCity, $pincode)) {
                return true;
            }
        }

        // Any other state (e.g. Maharashtra, Karnataka, Tamil Nadu, Bihar, etc.) or non-allowlisted city is rejected
        return false;
    }

    /**
     * Check if city matches recognized Haryana NCR locality allowlist and postal prefix.
     */
    protected function matchesHaryanaNcr(string $normalizedCity, string $pincode): bool
    {
        foreach (self::HARYANA_NCR_LOCALITIES as $locality => $pinPrefix) {
            if (str_starts_with($pincode, $pinPrefix) && (str_contains($normalizedCity, $locality) || str_contains($locality, $normalizedCity))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if city matches recognized Uttar Pradesh NCR locality allowlist and postal prefix.
     */
    protected function matchesUpNcr(string $normalizedCity, string $pincode): bool
    {
        foreach (self::UP_NCR_LOCALITIES as $locality => $pinPrefix) {
            if (str_starts_with($pincode, $pinPrefix) && (str_contains($normalizedCity, $locality) || str_contains($locality, $normalizedCity))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Standard user-facing ineligibility message.
     */
    public function getIneligibilityMessage(): string
    {
        return 'Delivery is currently available only within Delhi NCR.';
    }
}
