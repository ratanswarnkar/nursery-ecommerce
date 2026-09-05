<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CartMergeService
{
    /**
     * Atomically merge guest session cart into authenticated customer cart.
     */
    public function mergeGuestCartIntoCustomerCart(string $guestSessionId, Customer $customer, ?int $guestCartId = null): void
    {
        if (empty($guestSessionId) && empty($guestCartId)) {
            return;
        }

        DB::transaction(function () use ($guestSessionId, $customer, $guestCartId) {
            // Find and lock guest cart (ensuring it does not belong to another customer)
            $guestCart = null;

            if ($guestCartId) {
                $guestCart = Cart::where('id', $guestCartId)
                    ->whereNull('customer_id')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $guestCart && ! empty($guestSessionId)) {
                $guestCart = Cart::where('session_id', $guestSessionId)
                    ->whereNull('customer_id')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $guestCart || $guestCart->items()->count() === 0) {
                return;
            }

            // Find or create customer's active cart
            $customerCart = Cart::firstOrCreate(
                ['customer_id' => $customer->id, 'is_active' => true],
                ['last_activity_at' => now()]
            );

            $customerCart = Cart::where('id', $customerCart->id)->lockForUpdate()->first();

            $guestCart->load('items.productVariant.product');

            foreach ($guestCart->items as $guestItem) {
                $variant = $guestItem->productVariant;

                // Only merge active, non-deleted items
                if (! $variant || ! $variant->is_active || $variant->trashed()) {
                    continue;
                }

                if (! $variant->product || ! $variant->product->is_active || $variant->product->trashed()) {
                    continue;
                }

                $availableStock = $variant->available_stock;
                if ($availableStock <= 0) {
                    continue;
                }

                $existingCustomerItem = CartItem::where('cart_id', $customerCart->id)
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingCustomerItem) {
                    $combinedQty = $existingCustomerItem->quantity + $guestItem->quantity;
                    $finalQty = min(50, min($availableStock, $combinedQty));
                    $existingCustomerItem->update(['quantity' => $finalQty]);
                } else {
                    $finalQty = min(50, min($availableStock, $guestItem->quantity));
                    if ($finalQty > 0) {
                        CartItem::create([
                            'cart_id' => $customerCart->id,
                            'product_variant_id' => $variant->id,
                            'quantity' => $finalQty,
                            'custom_options' => $guestItem->custom_options,
                        ]);
                    }
                }
            }

            // Deactivate and delete guest cart
            $guestCart->items()->delete();
            $guestCart->update(['is_active' => false, 'session_id' => null]);

            $customerCart->update(['last_activity_at' => now()]);
        });
    }
}
