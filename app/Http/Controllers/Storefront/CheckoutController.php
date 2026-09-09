<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Cart\CartService;
use App\Services\Order\OrderCalculationService;
use App\Services\Order\OrderCreationService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderCalculationService $calculationService,
        protected OrderCreationService $orderCreationService,
        protected PaymentService $paymentService
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
     * Process checkout form submission, create order, and initiate payment.
     */
    public function store(CheckoutRequest $request): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $cart = $this->cartService->getOrCreateCart($customer, $request->session()->getId());

        try {
            $order = $this->orderCreationService->createOrder($customer, $cart, $request->validated());
            $gatewayName = (string) config('payment.default', 'null');

            // If AJAX/JSON requested (e.g. from Razorpay Standard Checkout in frontend)
            if ($request->expectsJson() || $request->wantsJson()) {
                $paymentResponse = $this->paymentService->initiatePayment($order, $gatewayName, customer: $customer);

                return response()->json([
                    'success' => true,
                    'order_number' => $order->order_number,
                    'gateway' => $gatewayName,
                    'transaction_number' => $paymentResponse->transactionNumber,
                    'razorpay_order_id' => $paymentResponse->gatewayReference,
                    'key_id' => (string) config('services.razorpay.key_id', ''),
                    'amount' => (int) round(((float) $order->grand_total) * 100),
                    'currency' => $order->currency ?: 'INR',
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'redirect_url' => route('checkout.success', $order->order_number),
                ]);
            }

            // If Razorpay gateway is active and standard non-JSON form was submitted
            if ($gatewayName === 'razorpay') {
                $this->paymentService->initiatePayment($order, 'razorpay', customer: $customer);

                return redirect()->route('checkout.payment', $order->order_number);
            }

            // Null gateway / default web redirect
            return redirect()->route('checkout.success', $order->order_number)
                ->with('success', 'Your order has been placed successfully!');
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            return redirect()->route('checkout.index')
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display Razorpay payment page for pending order.
     */
    public function payment(string $orderNumber): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::with(['items.productVariant.product', 'paymentTransactions'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        abort_unless((int) $order->customer_id === (int) $customer->id, 404);

        // If order is already paid, redirect to success
        if ($order->payment_status === PaymentStatus::PAID) {
            return redirect()->route('checkout.success', $order->order_number)
                ->with('info', 'This order is already paid.');
        }

        // Retrieve active pending transaction or initiate via PaymentService
        $transaction = $order->paymentTransactions()
            ->where('status', PaymentStatus::PENDING)
            ->latest('id')
            ->first();

        if (! $transaction) {
            $initiation = $this->paymentService->initiatePayment($order, 'razorpay', customer: $customer);
            $transaction = $order->paymentTransactions()
                ->where('transaction_number', $initiation->transactionNumber)
                ->firstOrFail();
        }

        $razorpayOrderId = $transaction->gateway_transaction_id
            ?? $transaction->payload['gateway_reference']
            ?? $transaction->payload['razorpay_order_id']
            ?? null;

        return view('storefront.checkout.payment', [
            'order' => $order,
            'customer' => $customer,
            'transaction' => $transaction,
            'razorpayOrderId' => $razorpayOrderId,
            'keyId' => (string) config('services.razorpay.key_id', ''),
            'amountInPaise' => (int) round(((float) $order->grand_total) * 100),
        ]);
    }

    /**
     * Verify payment signature server-side.
     */
    public function verifyPayment(Request $request): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'transaction_number' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $transaction = PaymentTransaction::with('order')
            ->where('transaction_number', $validated['transaction_number'])
            ->firstOrFail();

        abort_unless((int) $transaction->order->customer_id === (int) $customer->id, 404);

        try {
            $verificationResponse = $this->paymentService->processPaymentVerification(
                transaction: $transaction,
                payload: $validated
            );

            if ($verificationResponse->success && $verificationResponse->status === PaymentStatus::PAID) {
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified successfully.',
                        'redirect_url' => route('checkout.success', $transaction->order->order_number),
                    ]);
                }

                return redirect()->route('checkout.success', $transaction->order->order_number)
                    ->with('success', 'Payment completed successfully!');
            }

            $errorMessage = $verificationResponse->failureMessage ?: 'Payment verification failed.';

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()->route('checkout.payment', $transaction->order->order_number)
                ->with('error', $errorMessage);
        } catch (Throwable $e) {
            $message = 'Payment verification could not be completed.';

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('checkout.payment', $transaction->order->order_number)
                ->with('error', $message);
        }
    }

    /**
     * Cancel pending payment transaction upon customer dismissal.
     */
    public function cancelPayment(Request $request): JsonResponse|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'transaction_number' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = PaymentTransaction::with('order')
            ->where('transaction_number', $validated['transaction_number'])
            ->firstOrFail();

        abort_unless((int) $transaction->order->customer_id === (int) $customer->id, 404);

        if ($transaction->status === PaymentStatus::PENDING) {
            $this->paymentService->cancelPayment(
                $transaction,
                $validated['reason'] ?? 'Payment cancelled by customer.'
            );
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled.',
            ]);
        }

        return redirect()->route('account.orders.show', $transaction->order->order_number)
            ->with('info', 'Payment was cancelled.');
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
