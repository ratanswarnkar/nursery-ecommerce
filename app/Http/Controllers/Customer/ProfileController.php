<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $customer = Auth::guard('customer')->user();

        return view('customer.account.profile', [
            'customer' => $customer,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $customer->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        return redirect()->route('account.profile')
            ->with('success', 'Your profile information has been updated successfully.');
    }
}
