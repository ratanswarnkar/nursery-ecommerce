<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Services\Audit\AuditLogger;
use App\Services\Cart\CartService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    public function __construct(
        protected OrderNumberGenerator $orderNumberGenerator,
        protected OrderCalculationService $calculationService,
        protected InventoryService $inventoryService,
        protected CartService $cartService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create an order from an active customer cart transactionally.
     *
     * @throws ValidationException
     */
    public function createOrder(Customer $customer, Cart $cart, array $checkoutData): Order
    {
        return DB::transaction(function () use ($customer, $cart, $checkoutData) {
            // 1. Load cart items with variant and catalog associations
            $cart->load([
                'items.productVariant.product.taxClass.taxRules.taxRate',
                'items.productVariant.attributeValues.attribute',
            ]);

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your cart is empty. Please add items to your cart before checking out.'],
                ]);
            }

            // 2. Resolve and snapshot shipping address with strict IDOR verification
            $shippingAddressId = (int) ($checkoutData['shipping_address_id'] ?? 0);
            $shippingAddress = CustomerAddress::where('customer_id', $customer->id)
                ->where('id', $shippingAddressId)
                ->first();

            if (! $shippingAddress) {
                throw ValidationException::withMessages([
                    'shipping_address_id' => ['Please select a valid delivery address from your address book.'],
                ]);
            }

            $shippingAddressSnapshot = [
                'recipient_name' => $shippingAddress->recipient_name,
                'phone' => $shippingAddress->phone,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country ?? 'India',
                'address_type' => $shippingAddress->address_type?->value ?? 'home',
            ];

            // 3. Resolve and snapshot billing address
            $billingSameAsShipping = ! empty($checkoutData['billing_same_as_shipping']);
            if ($billingSameAsShipping) {
                $billingAddressSnapshot = $shippingAddressSnapshot;
            } else {
                $billingAddressId = (int) ($checkoutData['billing_address_id'] ?? 0);
                $billingAddress = CustomerAddress::where('customer_id', $customer->id)
                    ->where('id', $billingAddressId)
                    ->first();

                if (! $billingAddress) {
                    // Fallback to shipping address if billing address id invalid or not provided
                    $billingAddressSnapshot = $shippingAddressSnapshot;
                } else {
                    $billingAddressSnapshot = [
                        'recipient_name' => $billingAddress->recipient_name,
                        'phone' => $billingAddress->phone,
                        'address_line_1' => $billingAddress->address_line_1,
                        'address_line_2' => $billingAddress->address_line_2,
                        'city' => $billingAddress->city,
                        'state' => $billingAddress->state,
                        'postal_code' => $billingAddress->postal_code,
                        'country' => $billingAddress->country ?? 'India',
                        'address_type' => $billingAddress->address_type?->value ?? 'home',
                    ];
                }
            }

            // 4. Server-side validation of each item (catalog active state, price, stock limits)
            $itemsForCalculation = [];
            foreach ($cart->items as $item) {
                $variant = $item->productVariant;
                $product = $variant?->product;

                if (! $variant || ! $product || ! $variant->is_active || ! $product->is_active || $variant->trashed() || $product->trashed()) {
                    throw ValidationException::withMessages([
                        'cart' => ["One or more items in your cart ('{$item->id}') are no longer available."],
                    ]);
                }

                if ($item->quantity < 1 || $item->quantity > 50) {
                    throw ValidationException::withMessages([
                        'quantity' => ["Quantity for '{$product->name}' must be between 1 and 50."],
                    ]);
                }

                $itemsForCalculation[] = [
                    'variant' => $variant,
                    'quantity' => $item->quantity,
                ];
            }

            // 5. Compute order pricing breakdown with deterministic BCMath
            $totals = $this->calculationService->calculateOrder(
                $itemsForCalculation,
                $shippingAddressSnapshot,
                $checkoutData['coupon_code'] ?? null
            );

            // 6. Generate non-sequential unique order number
            $orderNumber = $this->orderNumberGenerator->generate();

            // 7. Create Order Record with full snapshots
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'shipping_status' => ShippingStatus::UNFULFILLED,
                'currency' => 'INR',
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'shipping_amount' => $totals['shipping_amount'],
                'discount_amount' => $totals['discount_amount'],
                'grand_total' => $totals['grand_total'],
                'shipping_address_json' => $shippingAddressSnapshot,
                'billing_address_json' => $billingAddressSnapshot,
                'coupon_id' => null,
                'coupon_code' => null,
                'notes' => ! empty($checkoutData['notes']) ? strip_tags(trim($checkoutData['notes'])) : null,
            ]);

            // 8. Create OrderItem records and execute stock deduction
            foreach ($totals['lines'] as $line) {
                $variant = $line['variant'];
                $product = $variant->product;

                $variantName = $variant->attributeValues->isNotEmpty()
                    ? $variant->attributeValues->pluck('value')->implode(' / ')
                    : 'Standard';

                $metadata = [
                    'sku' => $variant->sku,
                    'attributes' => $variant->attributeValues->mapWithKeys(
                        fn ($av) => [$av->attribute?->name ?? 'Option' => $av->value]
                    )->toArray(),
                ];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'variant_name' => $variantName,
                    'sku' => $variant->sku,
                    'price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                    'tax_amount' => $line['tax_amount'],
                    'discount_amount' => $line['discount_amount'],
                    'total' => $line['total'],
                    'metadata' => $metadata,
                ]);

                // Atomically deduct inventory with pessimistic locks and outbound movement ledger
                $this->inventoryService->deductStockForOrder($variant, $line['quantity'], $order);
            }

            // 9. Record initial order status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => OrderStatus::PENDING->value,
                'comment' => 'Order created via storefront checkout.',
                'changed_by_type' => Customer::class,
                'changed_by_id' => $customer->id,
            ]);

            // 10. Audit customer security event
            $this->auditLogger->logCustomerEvent('order.created', $customer, [
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total,
                'items_count' => count($totals['lines']),
            ], $order);

            // 11. Clear purchased cart contents safely
            $this->cartService->clearCart($cart);

            return $order;
        });
    }
}
