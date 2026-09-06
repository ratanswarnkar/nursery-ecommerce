<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;

class CustomerAddressService
{
    /**
     * Create a new customer address with transaction-safe default invariant.
     */
    public function createAddress(Customer $customer, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $data) {
            // Lock the owning customer row to serialize concurrent address changes
            Customer::where('id', $customer->id)->lockForUpdate()->first();

            $existingCount = $customer->addresses()->count();
            $shouldBeDefault = ! empty($data['is_default']) || $existingCount === 0;

            if ($shouldBeDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            return $customer->addresses()->create(array_merge($data, [
                'is_default' => $shouldBeDefault,
            ]));
        });
    }

    /**
     * Update an address with transaction-safe default invariant.
     */
    public function updateAddress(Customer $customer, CustomerAddress $address, array $data): CustomerAddress
    {
        abort_unless($address->customer_id === $customer->id, 404);

        return DB::transaction(function () use ($customer, $address, $data) {
            Customer::where('id', $customer->id)->lockForUpdate()->first();

            $requestedDefault = ! empty($data['is_default']);

            if ($requestedDefault) {
                $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
                $address->update(array_merge($data, ['is_default' => true]));
            } else {
                // If it was default and user tried to unset, ensure at least one default remains
                if ($address->is_default) {
                    $otherAddress = $customer->addresses()->where('id', '!=', $address->id)->orderBy('id')->first();
                    if ($otherAddress) {
                        $otherAddress->update(['is_default' => true]);
                        $address->update(array_merge($data, ['is_default' => false]));
                    } else {
                        // Only address must remain default
                        $address->update(array_merge($data, ['is_default' => true]));
                    }
                } else {
                    $address->update(array_merge($data, ['is_default' => false]));
                }
            }

            return $address->fresh();
        });
    }

    /**
     * Delete an address with transaction-safe default promotion.
     */
    public function deleteAddress(Customer $customer, CustomerAddress $address): void
    {
        abort_unless($address->customer_id === $customer->id, 404);

        DB::transaction(function () use ($customer, $address) {
            Customer::where('id', $customer->id)->lockForUpdate()->first();

            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $oldestRemaining = $customer->addresses()->orderBy('id')->first();
                $oldestRemaining?->update(['is_default' => true]);
            }
        });
    }

    /**
     * Explicitly set an address as the default address.
     */
    public function setDefaultAddress(Customer $customer, CustomerAddress $address): CustomerAddress
    {
        abort_unless($address->customer_id === $customer->id, 404);

        return DB::transaction(function () use ($customer, $address) {
            Customer::where('id', $customer->id)->lockForUpdate()->first();

            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            return $address->fresh();
        });
    }
}
