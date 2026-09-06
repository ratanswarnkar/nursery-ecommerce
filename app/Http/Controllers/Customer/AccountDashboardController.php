<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountDashboardController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): View
    {
        $customer = Auth::guard('customer')->user();

        // Get customer addresses
        $addresses = $customer->addresses()->orderByDesc('is_default')->take(3)->get();
        $defaultAddress = $addresses->firstWhere('is_default', true);

        // Get active cart summary
        $cart = $this->cartService->getOrCreateCart($customer, $request->session()->getId());
        $cartDetails = $this->cartService->getCartDetails($cart);

        return view('customer.account.dashboard', [
            'customer' => $customer,
            'defaultAddress' => $defaultAddress,
            'addressCount' => $customer->addresses()->count(),
            'cartCount' => $cartDetails['item_count'],
            'cartItemCount' => $cartDetails['item_count'],
            'cartSubtotal' => $cartDetails['subtotal'],
        ]);
    }
}
