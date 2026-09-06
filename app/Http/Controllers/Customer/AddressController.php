<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Services\Customer\CustomerAddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function __construct(
        protected CustomerAddressService $addressService
    ) {}

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $addresses = $customer->addresses()->orderByDesc('is_default')->latest('id')->get();

        return view('customer.account.addresses.index', [
            'customer' => $customer,
            'addresses' => $addresses,
        ]);
    }

    public function store(CustomerAddressRequest $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $this->addressService->createAddress($customer, $request->validated());

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address saved successfully.');
    }

    public function update(CustomerAddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        // Enforce IDOR protection: Address must belong to customer
        abort_unless($address->customer_id === $customer->id, 404);

        $this->addressService->updateAddress($customer, $address, $request->validated());

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address updated successfully.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($address->customer_id === $customer->id, 404);

        $this->addressService->deleteAddress($customer, $address);

        return redirect()->route('account.addresses.index')
            ->with('success', 'Address deleted successfully.');
    }

    public function setDefault(CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($address->customer_id === $customer->id, 404);

        $this->addressService->setDefaultAddress($customer, $address);

        return redirect()->route('account.addresses.index')
            ->with('success', 'Default address updated.');
    }
}
