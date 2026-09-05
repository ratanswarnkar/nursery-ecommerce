<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Resolve or instantiate active cart for customer or guest session.
     */
    public function getOrCreateCart(?Customer $customer, ?string $sessionId, ?int $sessionCartId = null): Cart
    {
        if ($customer) {
            return Cart::firstOrCreate(
                ['customer_id' => $customer->id, 'is_active' => true],
                ['session_id' => $sessionId, 'last_activity_at' => now()]
            );
        }

        // 1. Check if session holds a known active guest cart
        if ($sessionCartId) {
            $cart = Cart::where('id', $sessionCartId)
                ->whereNull('customer_id')
                ->where('is_active', true)
                ->first();

            if ($cart) {
                if ($sessionId && $cart->session_id !== $sessionId) {
                    $cart->update(['session_id' => $sessionId, 'last_activity_at' => now()]);
                }

                return $cart;
            }
        }

        // 2. Check by session_id
        if ($sessionId) {
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->where('is_active', true)
                ->first();

            if ($cart) {
                $cart->update(['last_activity_at' => now()]);

                return $cart;
            }
        }

        return Cart::create([
            'session_id' => $sessionId,
            'customer_id' => null,
            'is_active' => true,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Add a variant item to the cart with server-side stock and catalog validation.
     */
    public function addItem(Cart $cart, int $variantId, int $quantity = 1, ?array $options = null): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be at least 1.'],
            ]);
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity, $options) {
            // Validate variant and parent product state
            $variant = ProductVariant::with('product')->findOrFail($variantId);

            if (! $variant->is_active || $variant->trashed()) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['The selected product variant is currently unavailable.'],
                ]);
            }

            if (! $variant->product || ! $variant->product->is_active || $variant->product->trashed()) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['The selected product is currently unavailable.'],
                ]);
            }

            $availableStock = $variant->available_stock;
            if ($availableStock <= 0) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ["'{$variant->product->name}' ({$variant->sku}) is currently out of stock."],
                ]);
            }

            // Pessimistic lock on cart item if already present
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $currentInCart = $existingItem ? $existingItem->quantity : 0;
            $targetQuantity = $currentInCart + $quantity;

            if ($targetQuantity > 50) {
                throw ValidationException::withMessages([
                    'quantity' => ["Maximum allowed quantity per item is 50. You currently have {$currentInCart} in your cart."],
                ]);
            }

            if ($targetQuantity > $availableStock) {
                $canAdd = max(0, $availableStock - $currentInCart);
                throw ValidationException::withMessages([
                    'quantity' => ["Cannot add {$quantity} more. Only {$availableStock} available in stock (you currently have {$currentInCart} in your cart)."],
                ]);
            }

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $targetQuantity,
                    'custom_options' => $options ?: $existingItem->custom_options,
                ]);
                $item = $existingItem;
            } else {
                $item = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $targetQuantity,
                    'custom_options' => $options,
                ]);
            }

            $cart->update(['last_activity_at' => now()]);

            return $item;
        });
    }

    /**
     * Update item quantity within cart with IDOR protection and stock revalidation.
     */
    public function updateQuantity(Cart $cart, CartItem $item, int $newQuantity): ?CartItem
    {
        abort_unless($item->cart_id === $cart->id, 404);

        if ($newQuantity <= 0) {
            $this->removeItem($cart, $item);

            return null;
        }

        if ($newQuantity > 50) {
            throw ValidationException::withMessages([
                'quantity' => ['Maximum allowed quantity per item is 50.'],
            ]);
        }

        return DB::transaction(function () use ($cart, $item, $newQuantity) {
            $item->load('productVariant.product');
            $variant = $item->productVariant;

            if (! $variant || ! $variant->is_active || $variant->trashed()) {
                throw ValidationException::withMessages([
                    'quantity' => ['This variant is no longer available.'],
                ]);
            }

            $available = $variant->available_stock;
            if ($newQuantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => ["Cannot update to {$newQuantity}. Only {$available} available in stock."],
                ]);
            }

            $item->update(['quantity' => $newQuantity]);
            $cart->update(['last_activity_at' => now()]);

            return $item->fresh();
        });
    }

    /**
     * Remove item from cart with IDOR verification.
     */
    public function removeItem(Cart $cart, CartItem $item): void
    {
        abort_unless($item->cart_id === $cart->id, 404);

        $item->delete();
        $cart->update(['last_activity_at' => now()]);
    }

    /**
     * Clear all items from cart.
     */
    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['last_activity_at' => now()]);
    }

    /**
     * Compile comprehensive cart details with dynamic stock resolution and BCMath money calculation.
     */
    public function getCartDetails(Cart $cart): array
    {
        $cart->load([
            'items.productVariant.product',
            'items.productVariant.attributeValues.attribute',
        ]);

        $subtotal = '0.00';
        $itemCount = 0;
        $hasIssues = false;
        $compiledItems = [];

        foreach ($cart->items as $item) {
            $variant = $item->productVariant;
            $product = $variant?->product;

            $status = 'in_stock';
            $isPurchasable = true;
            $message = null;

            // 1. Check Catalog Active State
            if (! $variant || ! $product || ! $variant->is_active || ! $product->is_active || $variant->trashed() || $product->trashed()) {
                $status = 'unavailable';
                $isPurchasable = false;
                $message = 'This item is no longer available.';
                $hasIssues = true;
            } else {
                // 2. Check Real-Time Available Stock
                $availableStock = $variant->available_stock;

                if ($availableStock <= 0) {
                    $status = 'out_of_stock';
                    $isPurchasable = false;
                    $message = 'This item is currently out of stock.';
                    $hasIssues = true;
                } elseif ($availableStock < $item->quantity) {
                    $status = 'insufficient_stock';
                    $isPurchasable = false;
                    $message = "Only {$availableStock} available in stock. Please adjust quantity.";
                    $hasIssues = true;
                }
            }

            $price = $variant ? (string) $variant->price : '0.00';
            $lineTotal = bcmul($price, (string) $item->quantity, 2);

            if ($isPurchasable) {
                $subtotal = bcadd($subtotal, $lineTotal, 2);
                $itemCount += $item->quantity;
            }

            $compiledItems[] = [
                'id' => $item->id,
                'cart_item' => $item,
                'product' => $product,
                'variant' => $variant,
                'quantity' => $item->quantity,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'status' => $status,
                'is_purchasable' => $isPurchasable,
                'message' => $message,
                'available_stock' => $variant ? $variant->available_stock : 0,
            ];
        }

        return [
            'cart' => $cart,
            'items' => $compiledItems,
            'subtotal' => $subtotal,
            'item_count' => $itemCount,
            'has_issues' => $hasIssues,
        ];
    }
}
