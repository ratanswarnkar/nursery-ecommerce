<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Models\TenderItem;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenderItemController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Store a new item under a tender.
     */
    public function store(Request $request, Tender $tender): RedirectResponse
    {
        $validated = $request->validate([
            'item_code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:1000'],
            'unit' => ['required', 'string', 'max:50'],
            'government_quantity' => ['required', 'numeric', 'min:0.01'],
            'government_rate' => ['required', 'numeric', 'min:0'],
            'quoted_rate' => ['nullable', 'numeric', 'min:0'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['government_amount'] = (float) $validated['government_quantity'] * (float) $validated['government_rate'];

        if (! empty($validated['quoted_rate'])) {
            $validated['quoted_amount'] = (float) $validated['government_quantity'] * (float) $validated['quoted_rate'];
            $validated['final_rate'] = $validated['quoted_rate'];
        } else {
            $validated['final_rate'] = $validated['government_rate'];
        }

        $validated['tender_id'] = $tender->id;

        $item = TenderItem::create($validated);

        $this->auditLogger->logAdminEvent('tender_item.created', auth('admin')->user(), [
            'tender_id' => $tender->id,
            'description' => $item->description,
        ], $item);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', 'Tender item added successfully.');
    }

    /**
     * Remove an item from the tender.
     */
    public function destroy(Tender $tender, TenderItem $item): RedirectResponse
    {
        abort_unless((int) $item->tender_id === (int) $tender->id, 404);

        $item->delete();

        $this->auditLogger->logAdminEvent('tender_item.deleted', auth('admin')->user(), [
            'item_id' => $item->id,
        ], $tender);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', 'Tender item removed.');
    }
}
