<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreOrderReturnRequest;
use App\Models\Order;
use App\Services\Order\OrderReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerReturnController extends Controller
{
    /**
     * Show return request form for an eligible order.
     */
    public function create(string $orderNumber, OrderReturnService $orderReturnService): View|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::with(['items.productVariant.product', 'returns'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Strict IDOR protection
        abort_unless($order->customer_id === $customer->id, 404);

        if (! $orderReturnService->canRequestReturn($order)) {
            return redirect()
                ->route('account.orders.show', $order->order_number)
                ->with('error', 'This order is not currently eligible for return.');
        }

        $items = $orderReturnService->getReturnableItems($order);

        return view('customer.account.orders.return', [
            'customer' => $customer,
            'order' => $order,
            'items' => $items,
        ]);
    }

    /**
     * Submit a return request for eligible order items.
     */
    public function store(
        StoreOrderReturnRequest $request,
        string $orderNumber,
        OrderReturnService $orderReturnService
    ): RedirectResponse {
        $customer = Auth::guard('customer')->user();

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Strict IDOR protection
        abort_unless($order->customer_id === $customer->id, 404);

        $orderReturnService->createReturnRequest(
            order: $order,
            itemsData: $request->getItemsData(),
            reason: $request->input('reason'),
            customer: $customer
        );

        return redirect()
            ->route('account.orders.show', $order->order_number)
            ->with('success', 'Your return request has been submitted successfully and is pending review.');
    }
}
