<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * Resolve the active cart for the current user or guest session.
     */
    private function resolveCart(Request $request): Cart
    {
        $customer = Auth::guard('customer')->user();
        $sessionId = $request->session()->getId();
        $sessionCartId = $request->session()->get('cart_id');

        $cart = $this->cartService->getOrCreateCart($customer, $sessionId, $sessionCartId);

        if (! $customer) {
            $request->session()->put('cart_id', $cart->id);
        }

        return $cart;
    }

    /**
     * Display the cart contents.
     */
    public function index(Request $request): View|JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cartDetails = $this->cartService->getCartDetails($cart);

        if ($request->wantsJson()) {
            return response()->json($cartDetails);
        }

        return view('cart.index', $cartDetails);
    }

    /**
     * Add an item to the cart.
     */
    public function store(AddToCartRequest $request): RedirectResponse|JsonResponse
    {
        $cart = $this->resolveCart($request);

        $item = $this->cartService->addItem(
            $cart,
            (int) $request->validated('product_variant_id'),
            (int) $request->validated('quantity'),
            $request->input('options')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Item added to cart successfully.',
                'item' => $item,
            ], 201);
        }

        return back()->with('success', 'Item added to your cart.');
    }

    /**
     * Update item quantity in the cart.
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $cart = $this->resolveCart($request);

        $updated = $this->cartService->updateQuantity(
            $cart,
            $cartItem,
            (int) $request->validated('quantity')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $updated ? 'Cart updated successfully.' : 'Item removed from cart.',
                'item' => $updated,
            ]);
        }

        $message = $updated ? 'Cart updated successfully.' : 'Item removed from cart.';

        return back()->with('success', $message);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $cart = $this->resolveCart($request);

        $this->cartService->removeItem($cart, $cartItem);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Item removed from cart.',
            ]);
        }

        return back()->with('success', 'Item removed from your cart.');
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $cart = $this->resolveCart($request);

        $this->cartService->clearCart($cart);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cart cleared.',
            ]);
        }

        return back()->with('success', 'Your cart has been cleared.');
    }
}
