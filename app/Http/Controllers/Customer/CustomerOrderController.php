<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    /**
     * Display a paginated list of the customer's orders.
     */
    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $orders = $customer->orders()
            ->with(['items'])
            ->latest('id')
            ->paginate(10);

        return view('customer.account.orders.index', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    /**
     * Display detailed order view with IDOR protection.
     */
    public function show(string $orderNumber): View
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::with(['items.productVariant.product', 'statusHistories'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Enforce strict IDOR protection
        abort_unless($order->customer_id === $customer->id, 404);

        return view('customer.account.orders.show', [
            'customer' => $customer,
            'order' => $order,
        ]);
    }
}
