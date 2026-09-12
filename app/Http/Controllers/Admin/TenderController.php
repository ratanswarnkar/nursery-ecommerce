<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderPricingMode;
use App\Enums\TenderStatus;
use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class TenderController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Display a listing of all tenders.
     */
    public function index(Request $request): View
    {
        $query = Tender::withCount(['items', 'requirements', 'documents', 'bills'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pricing_mode')) {
            $query->where('pricing_mode', $request->pricing_mode);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('tender_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%");
            });
        }

        $tenders = $query->paginate(15)->withQueryString();

        return view('admin.tenders.index', [
            'tenders' => $tenders,
            'statuses' => TenderStatus::cases(),
            'pricingModes' => TenderPricingMode::cases(),
            'filters' => $request->only(['status', 'pricing_mode', 'q']),
        ]);
    }

    /**
     * Show the form for creating a new tender.
     */
    public function create(): View
    {
        return view('admin.tenders.create', [
            'pricingModes' => TenderPricingMode::cases(),
            'statuses' => TenderStatus::cases(),
        ]);
    }

    /**
     * Store a newly created tender in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tender_number' => ['required', 'string', 'max:100', 'unique:tenders,tender_number'],
            'name' => ['required', 'string', 'max:255'],
            'department_name' => ['required', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'original_soq_value' => ['required', 'numeric', 'min:0'],
            'awarded_value' => ['required', 'numeric', 'min:0'],
            'below_above_percentage' => ['nullable', 'numeric', 'between:-100,100'],
            'pricing_mode' => ['required', new Enum(TenderPricingMode::class)],
            'status' => ['nullable', new Enum(TenderStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'special_billing_enabled' => ['boolean'],
        ]);

        $validated['status'] = $validated['status'] ?? TenderStatus::DRAFT;
        $validated['special_billing_enabled'] = $request->boolean('special_billing_enabled');

        $tender = Tender::create($validated);

        $this->auditLogger->logAdminEvent('tender.created', auth('admin')->user(), [
            'tender_number' => $tender->tender_number,
            'awarded_value' => $tender->awarded_value,
        ], $tender);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "Tender #{$tender->tender_number} created successfully.");
    }

    /**
     * Display the specified tender details and relations.
     */
    public function show(Tender $tender): View
    {
        $tender->load([
            'items.product',
            'items.productVariant',
            'requirements.items',
            'documents',
            'bills.items',
        ]);

        return view('admin.tenders.show', [
            'tender' => $tender,
            'statuses' => TenderStatus::cases(),
        ]);
    }

    /**
     * Show the form for editing the specified tender.
     */
    public function edit(Tender $tender): View
    {
        return view('admin.tenders.edit', [
            'tender' => $tender,
            'pricingModes' => TenderPricingMode::cases(),
            'statuses' => TenderStatus::cases(),
        ]);
    }

    /**
     * Update the specified tender in storage.
     */
    public function update(Request $request, Tender $tender): RedirectResponse
    {
        $validated = $request->validate([
            'tender_number' => ['required', 'string', 'max:100', 'unique:tenders,tender_number,'.$tender->id],
            'name' => ['required', 'string', 'max:255'],
            'department_name' => ['required', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'original_soq_value' => ['required', 'numeric', 'min:0'],
            'awarded_value' => ['required', 'numeric', 'min:0'],
            'below_above_percentage' => ['nullable', 'numeric', 'between:-100,100'],
            'pricing_mode' => ['required', new Enum(TenderPricingMode::class)],
            'status' => ['required', new Enum(TenderStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'special_billing_enabled' => ['boolean'],
        ]);

        $validated['special_billing_enabled'] = $request->boolean('special_billing_enabled');

        $tender->update($validated);

        $this->auditLogger->logAdminEvent('tender.updated', auth('admin')->user(), [
            'tender_number' => $tender->tender_number,
        ], $tender);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "Tender #{$tender->tender_number} updated successfully.");
    }

    /**
     * Transition tender status workflow.
     */
    public function updateStatus(Request $request, Tender $tender): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(TenderStatus::class)],
        ]);

        $oldStatus = $tender->status->value;
        $tender->update(['status' => $validated['status']]);

        $this->auditLogger->logAdminEvent('tender.status_updated', auth('admin')->user(), [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
        ], $tender);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', 'Tender status updated to '.ucfirst($validated['status']).'.');
    }

    /**
     * Toggle special billing flag.
     */
    public function toggleSpecialBilling(Tender $tender): RedirectResponse
    {
        $tender->update([
            'special_billing_enabled' => ! $tender->special_billing_enabled,
        ]);

        $this->auditLogger->logAdminEvent('tender.special_billing_toggled', auth('admin')->user(), [
            'special_billing_enabled' => $tender->special_billing_enabled,
        ], $tender);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', 'Special billing flag updated.');
    }

    /**
     * Soft-delete or archive the tender.
     */
    public function destroy(Tender $tender): RedirectResponse
    {
        $tenderNumber = $tender->tender_number;
        $tender->delete();

        $this->auditLogger->logAdminEvent('tender.deleted', auth('admin')->user(), [
            'tender_number' => $tenderNumber,
        ], $tender);

        return redirect()->route('admin.tenders.index')
            ->with('success', "Tender #{$tenderNumber} has been archived.");
    }
}
