<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PolicyController extends Controller
{
    /**
     * Display Privacy Policy.
     */
    public function privacy(): View
    {
        return view('storefront.policies.privacy');
    }

    /**
     * Display Terms & Conditions.
     */
    public function terms(): View
    {
        return view('storefront.policies.terms');
    }

    /**
     * Display Shipping & Delivery Policy.
     */
    public function shipping(): View
    {
        return view('storefront.policies.shipping');
    }

    /**
     * Display Cancellation & Refund / Return Policy.
     */
    public function refund(): View
    {
        return view('storefront.policies.refund');
    }

    /**
     * Display Contact Us page.
     */
    public function contact(): View
    {
        return view('storefront.policies.contact', [
            'business' => [
                'name' => 'Sugandha Farms and Nursery',
                'address' => 'Mann Enclave, near Gurukul, Vill, Khera Khurd, Delhi, 110082',
                'phone' => '098111 14365',
                'country' => 'India',
            ],
        ]);
    }
}
