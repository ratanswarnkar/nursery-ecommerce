<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\Order\InvalidOrderStatusTransitionException;
use App\Exceptions\Shipping\IneligibleForShipmentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateShipmentRequest;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Invoice\InvoiceService;
use App\Services\Order\OrderLifecycleService;
use App\Services\Shipping\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function __construct(
        protected OrderLifecycleService $lifecycleService
    ) {}

    /**
     * Display a listing of orders for administrators.
     */
    public function index(Request $request): View
    {
        $query = Order::with(['customer', 'items'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'orderStatuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'filters' => $request->only(['status', 'payment_status', 'q']),
        ]);
    }

    /**
     * Display the specified order details for administrators.
     */
    public function show(Order $order): View
    {
        $order->load(['customer', 'items.productVariant.product', 'statusHistories', 'paymentTransactions', 'cancellation', 'shipments']);

        return view('admin.orders.show', [
            'order' => $order,
            'availableTransitions' => $order->status->availableTransitions(),
        ]);
    }

    /**
     * Update the order status to a valid next lifecycle state.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(OrderStatus::class)],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = OrderStatus::from($validated['status']);
        $admin = auth('admin')->user();

        // If transitioning to CANCELLED, enforce orders.cancel permission
        if ($targetStatus === OrderStatus::CANCELLED) {
            abort_unless($admin && $admin->can('orders.cancel'), 403, 'Unauthorized to cancel orders.');
        } else {
            abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to update order status.');
        }

        try {
            $this->lifecycleService->transitionStatus(
                order: $order,
                targetStatus: $targetStatus,
                comment: $validated['comment'] ?? null,
                admin: $admin
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Order #{$order->order_number} status updated to {$targetStatus->value}.");
        } catch (InvalidOrderStatusTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the order with a mandatory cancellation reason.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.cancel'), 403, 'Unauthorized to cancel orders.');

        try {
            $this->lifecycleService->cancelOrder(
                order: $order,
                reason: $validated['reason'],
                admin: $admin
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Order #{$order->order_number} has been cancelled and stock released.");
        } catch (InvalidOrderStatusTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * View or download the tax invoice PDF for an order.
     */
    public function invoice(Order $order, InvoiceService $invoiceService): Response
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.view'), 403, 'Unauthorized to view order invoices.');

        abort_unless($invoiceService->canGenerateInvoice($order), 404, 'Tax invoice is not available for this order.');

        $invoice = $invoiceService->getOrCreateInvoiceForOrder($order);

        return $invoiceService->downloadPdfResponse($invoice);
    }

    /**
     * Dispatch order and create shipment record.
     */
    public function createShipment(
        CreateShipmentRequest $request,
        Order $order,
        ShipmentService $shipmentService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to dispatch orders.');

        try {
            $shipmentService->createShipment(
                order: $order,
                data: $request->validated(),
                admin: $admin
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Order #{$order->order_number} has been dispatched successfully.");
        } catch (IneligibleForShipmentException|InvalidOrderStatusTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark shipment as out for delivery.
     */
    public function markOutForDelivery(
        Request $request,
        Order $order,
        Shipment $shipment,
        ShipmentService $shipmentService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to update fulfillment status.');

        abort_unless((int) $shipment->order_id === (int) $order->id, 404);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $shipmentService->markOutForDelivery(
                shipment: $shipment,
                comment: $validated['comment'] ?? null,
                admin: $admin
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Shipment for order #{$order->order_number} is now out for delivery.");
        } catch (InvalidOrderStatusTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark shipment as delivered.
     */
    public function markDelivered(
        Request $request,
        Order $order,
        Shipment $shipment,
        ShipmentService $shipmentService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to update fulfillment status.');

        abort_unless((int) $shipment->order_id === (int) $order->id, 404);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $shipmentService->markDelivered(
                shipment: $shipment,
                comment: $validated['comment'] ?? null,
                admin: $admin
            );

            return redirect()->route('admin.orders.show', $order)
                ->with('success', "Shipment for order #{$order->order_number} has been marked as delivered.");
        } catch (InvalidOrderStatusTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }
}
