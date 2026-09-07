<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Order\OrderCalculationService;
use App\Services\Order\OrderCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderCalculationService $calculationService,
        protected OrderCreationService $orderCreationService
    ) {}

    /**
     * Display checkout review and address selection.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $cart = $this->cartService->getOrCreateCart($customer, $request->session()->getId());
        $cartDetails = $this->cartService->getCartDetails($cart);

        if (empty($cartDetails['items'])) {
            return redirect()->route('cart.index')
                ->with('info', 'Your cart is empty. Please select plants before proceeding to checkout.');
        }

        if ($cartDetails['has_issues']) {
            return redirect()->route('cart.index')
                ->with('error', 'Please resolve inventory alerts in your cart before proceeding to checkout.');
        }

        $addresses = $customer->addresses()->orderByDesc('is_default')->latest('id')->get();

        // Prepare items for calculation
        $itemsForCalc = [];
        foreach ($cartDetails['items'] as $item) {
            $itemsForCalc[] = [
                'variant' => $item['variant'],
                'quantity' => $item['quantity'],
            ];
        }

        // Default to default address for calculation if available
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();
        $shippingAddressData = $defaultAddress ? [
            'country' => $defaultAddress->country ?? 'IN',
            'state' => $defaultAddress->state,
        ] : null;

        $pricing = $this->calculationService->calculateOrder($itemsForCalc, $shippingAddressData);

        return view('storefront.checkout.index', [
            'customer' => $customer,
            'cart' => $cart,
            'cartDetails' => $cartDetails,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Process checkout form submission and create order.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $cart = $this->cartService->getOrCreateCart($customer, $request->session()->getId());

        try {
            $order = $this->orderCreationService->createOrder($customer, $cart, $request->validated());

            return redirect()->route('checkout.success', $order->order_number)
                ->with('success', 'Your order has been placed successfully!');
        } catch (ValidationException $e) {
            return redirect()->route('checkout.index')
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display order confirmation foundation page.
     */
    public function success(string $orderNumber): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::with(['items.productVariant', 'statusHistories'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Enforce IDOR protection: only the customer who placed the order can view confirmation
        abort_unless($order->customer_id === $customer->id, 404);

        return view('storefront.checkout.success', [
            'order' => $order,
            'customer' => $customer,
        ]);
    }
}
