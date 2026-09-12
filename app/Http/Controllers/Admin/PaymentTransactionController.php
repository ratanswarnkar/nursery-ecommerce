<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentTransactionController extends Controller
{
    /**
     * Display a listing of payment transactions with filters and KPI metrics.
     */
    public function index(Request $request): View
    {
        $query = PaymentTransaction::with(['order.customer'])->latest();

        // Search by transaction number, gateway transaction id, or order number
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('gateway_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by payment status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by payment gateway
        if ($gateway = $request->query('gateway')) {
            $query->where('gateway', $gateway);
        }

        // Filter by date range
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Metrics calculations
        $metrics = [
            'total_count' => PaymentTransaction::count(),
            'total_collected' => PaymentTransaction::where('status', PaymentStatus::PAID)->sum('amount'),
            'total_pending' => PaymentTransaction::where('status', PaymentStatus::PENDING)->sum('amount'),
            'total_refunded' => PaymentTransaction::whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED])->sum('amount'),
        ];

        $transactions = $query->paginate(15)->withQueryString();
        $statuses = PaymentStatus::cases();

        return view('admin.payments.index', compact('transactions', 'metrics', 'statuses'));
    }
}
