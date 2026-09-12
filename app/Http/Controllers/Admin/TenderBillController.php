<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderBillStatus;
use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Models\TenderBill;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TenderBillController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Store a new bill (RA Bill) for a tender.
     */
    public function store(Request $request, Tender $tender): RedirectResponse
    {
        $validated = $request->validate([
            'bill_number' => ['required', 'string', 'max:100', 'unique:tender_bills,bill_number'],
            'bill_date' => ['required', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', new Enum(TenderBillStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'tender_requirement_id' => ['nullable', 'exists:tender_requirements,id'],
        ]);

        $validated['tender_id'] = $tender->id;
        $validated['status'] = $validated['status'] ?? TenderBillStatus::DRAFT;
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0.00;
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0.00;

        $bill = TenderBill::create($validated);

        $this->auditLogger->logAdminEvent('tender_bill.created', auth('admin')->user(), [
            'tender_id' => $tender->id,
            'bill_number' => $bill->bill_number,
            'grand_total' => $bill->grand_total,
        ], $bill);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "RA Bill #{$bill->bill_number} created successfully.");
    }

    /**
     * Update bill status.
     */
    public function updateStatus(Request $request, Tender $tender, TenderBill $bill): RedirectResponse
    {
        abort_unless((int) $bill->tender_id === (int) $tender->id, 404);

        $validated = $request->validate([
            'status' => ['required', new Enum(TenderBillStatus::class)],
        ]);

        $oldStatus = $bill->status->value;
        $bill->update(['status' => $validated['status']]);

        $this->auditLogger->logAdminEvent('tender_bill.status_updated', auth('admin')->user(), [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
        ], $bill);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "Bill #{$bill->bill_number} marked as ".ucfirst($validated['status']).'.');
    }
}
