<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceManagementController extends Controller
{
    /**
     * Display a listing of generated tax invoices.
     */
    public function index(Request $request): View
    {
        $query = Invoice::with(['order.customer'])->latest('invoice_date');

        // Search by invoice number, order number, or customer name
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by date range
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }

        // Metrics
        $metrics = [
            'total_count' => Invoice::count(),
            'total_tax' => Invoice::sum('tax_amount'),
            'total_amount' => Invoice::sum('grand_total'),
        ];

        $invoices = $query->paginate(15)->withQueryString();

        return view('admin.invoices.index', compact('invoices', 'metrics'));
    }

    /**
     * Stream or download the tax invoice PDF document.
     */
    public function download(Invoice $invoice, InvoiceService $invoiceService): Response
    {
        return $invoiceService->downloadPdfResponse($invoice);
    }
}
