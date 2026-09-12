<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessOrderRefundRequest;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\Order\OrderReturnService;
use App\Services\Payment\PaymentService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderReturnController extends Controller
{
    /**
     * Display a listing of all customer return requests and statuses.
     */
    public function index(Request $request): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.view'), 403, 'Unauthorized to view returns.');

        $query = OrderReturn::with(['order.customer', 'orderItem'])->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $metrics = [
            'total' => OrderReturn::count(),
            'requested' => OrderReturn::where('status', ReturnStatus::REQUESTED)->count(),
            'approved' => OrderReturn::where('status', ReturnStatus::APPROVED)->count(),
            'completed' => OrderReturn::where('status', ReturnStatus::COMPLETED)->count(),
            'rejected' => OrderReturn::where('status', ReturnStatus::REJECTED)->count(),
        ];

        $returns = $query->paginate(15)->withQueryString();
        $statuses = ReturnStatus::cases();

        return view('admin.returns.index', compact('returns', 'metrics', 'statuses'));
    }

    /**
     * Approve a customer return request.
     */
    public function approve(
        Request $request,
        Order $order,
        OrderReturn $orderReturn,
        OrderReturnService $orderReturnService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to update returns.');
        abort_unless((int) $orderReturn->order_id === (int) $order->id, 404);

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $orderReturnService->approveReturn(
                orderReturn: $orderReturn,
                adminNotes: $validated['admin_notes'] ?? null,
                admin: $admin
            );

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', "Return #{$orderReturn->id} has been approved.");
        } catch (Exception $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a customer return request with mandatory reason.
     */
    public function reject(
        Request $request,
        Order $order,
        OrderReturn $orderReturn,
        OrderReturnService $orderReturnService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to update returns.');
        abort_unless((int) $orderReturn->order_id === (int) $order->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => 'A reason is required to reject a return request.',
        ]);

        try {
            $orderReturnService->rejectReturn(
                orderReturn: $orderReturn,
                reason: $validated['reason'],
                admin: $admin
            );

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', "Return #{$orderReturn->id} has been rejected.");
        } catch (Exception $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark physical return completed and optionally restock items.
     */
    public function complete(
        Request $request,
        Order $order,
        OrderReturn $orderReturn,
        OrderReturnService $orderReturnService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to update returns.');
        abort_unless((int) $orderReturn->order_id === (int) $order->id, 404);

        $restock = $request->boolean('restock');

        try {
            $orderReturnService->completeReturn(
                orderReturn: $orderReturn,
                admin: $admin,
                restock: $restock
            );

            $message = "Return #{$orderReturn->id} marked completed.".($restock ? ' Stock restocked successfully.' : '');

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', $message);
        } catch (Exception $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Explicitly restock returned items for a completed return.
     */
    public function restock(
        Order $order,
        OrderReturn $orderReturn,
        OrderReturnService $orderReturnService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to restock inventory.');
        abort_unless((int) $orderReturn->order_id === (int) $order->id, 404);

        try {
            $orderReturnService->restockReturn(
                orderReturn: $orderReturn,
                admin: $admin
            );

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', "Items for return #{$orderReturn->id} restocked successfully.");
        } catch (Exception $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Process a partial or full refund for an order.
     */
    public function processRefund(
        ProcessOrderRefundRequest $request,
        Order $order,
        PaymentService $paymentService
    ): RedirectResponse {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->can('orders.update'), 403, 'Unauthorized to process refunds.');

        $transaction = $order->paymentTransactions()
            ->whereIn('status', [PaymentStatus::PAID, PaymentStatus::PARTIALLY_REFUNDED])
            ->latest('id')
            ->first();

        if (! $transaction) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'No refundable payment transaction found for this order.');
        }

        $orderReturn = null;
        if ($request->filled('order_return_id')) {
            $orderReturn = OrderReturn::where('id', $request->input('order_return_id'))
                ->where('order_id', $order->id)
                ->first();
        }

        try {
            $orderRefund = $paymentService->processRefund(
                transaction: $transaction,
                amount: (float) $request->input('amount'),
                reason: $request->input('reason'),
                orderReturn: $orderReturn,
                admin: $admin,
                idempotencyKey: $request->input('idempotency_key')
            );

            if ($orderRefund->status === RefundStatus::PROCESSED) {
                return redirect()
                    ->route('admin.orders.show', $order)
                    ->with('success', 'Refund of ₹'.number_format($orderRefund->amount, 2).' processed successfully.');
            }

            $errorMsg = $orderRefund->payload['error'] ?? 'Refund gateway rejected the transaction.';

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'Refund failed: '.$errorMsg);
        } catch (Exception $e) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }
}
