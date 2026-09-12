<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderRequirementStatus;
use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Models\TenderRequirement;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TenderRequirementController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Store a new requirement demand under a tender.
     */
    public function store(Request $request, Tender $tender): RedirectResponse
    {
        $validated = $request->validate([
            'requirement_number' => ['required', 'string', 'max:100'],
            'requirement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', new Enum(TenderRequirementStatus::class)],
        ]);

        $validated['tender_id'] = $tender->id;
        $validated['status'] = $validated['status'] ?? TenderRequirementStatus::RECEIVED;
        $validated['source_type'] = 'manual';

        $req = TenderRequirement::create($validated);

        $this->auditLogger->logAdminEvent('tender_requirement.created', auth('admin')->user(), [
            'tender_id' => $tender->id,
            'requirement_number' => $req->requirement_number,
        ], $req);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "Requirement #{$req->requirement_number} recorded.");
    }
}
