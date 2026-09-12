@extends('layouts.admin')

@section('title', 'Tender #' . $tender->tender_number)
@section('header_title', 'Tender Overview')

@section('breadcrumbs')
    <span class="breadcrumbs-sep">/</span>
    <a href="{{ route('admin.tenders.index') }}">Tenders</a>
    <span class="breadcrumbs-sep">/</span>
    <span>#{{ $tender->tender_number }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <h1 class="page-title">{{ $tender->name }}</h1>
            @php
                $badgeClass = match($tender->status->value) {
                    'active' => 'badge-success',
                    'completed' => 'badge-primary',
                    'draft' => 'badge-warning',
                    'cancelled', 'archived' => 'badge-danger',
                    default => 'badge-neutral',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ ucfirst($tender->status->value) }}</span>
            @if($tender->special_billing_enabled)
                <span class="badge badge-primary">Special Billing</span>
            @endif
        </div>
        <div style="font-family: monospace; color: var(--primary-light); font-size: 0.875rem; margin-top: 0.25rem;">
            Tender #: {{ $tender->tender_number }} | Department: {{ $tender->department_name }}
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        @can('tenders.update', 'admin')
            <a href="{{ route('admin.tenders.edit', $tender) }}" class="btn btn-secondary">Edit Details</a>

            <form method="POST" action="{{ route('admin.tenders.toggle-special-billing', $tender) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary" title="Toggle special billing mode">
                    {{ $tender->special_billing_enabled ? 'Disable Special Billing' : 'Enable Special Billing' }}
                </button>
            </form>
        @endcan

        <a href="{{ route('admin.tenders.index') }}" class="btn btn-secondary">&larr; Back to Tenders</a>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Awarded Contract Value</div>
            <div class="stat-value" style="color: #10b981;">₹{{ number_format((float)$tender->awarded_value, 2) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Binding Tender Total</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Original SOQ Value</div>
            <div class="stat-value">₹{{ number_format((float)$tender->original_soq_value, 2) }}</div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">
                {{ $tender->below_above_percentage ? ($tender->below_above_percentage . '% variation') : 'Government estimate' }}
            </div>
        </div>
        <div class="stat-icon-wrapper">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Total Billed to Date</div>
            @php $totalBilled = $tender->bills->sum('grand_total'); @endphp
            <div class="stat-value" style="color: {{ $totalBilled > 0 ? '#38bdf8' : 'var(--text-main)' }};">
                ₹{{ number_format((float)$totalBilled, 2) }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">{{ $tender->bills->count() }} RA Bill(s)</div>
        </div>
        <div class="stat-icon-wrapper" style="background: rgba(2, 132, 199, 0.15); color: #38bdf8;">
            <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
    </div>
</div>

<!-- Workflow Status Transition Bar -->
@can('tenders.update', 'admin')
    <div class="card" style="padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-weight: 600; font-size: 0.875rem;">Contract Lifecycle Workflow</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Current Status: <span style="font-weight: 700; color: var(--text-main);">{{ ucfirst($tender->status->value) }}</span></div>
        </div>
        <form method="POST" action="{{ route('admin.tenders.update-status', $tender) }}" style="display: flex; gap: 0.5rem; align-items: center;">
            @csrf
            <select name="status" class="form-select" style="min-width: 150px; font-size: 0.8125rem;">
                @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ $tender->status->value === $st->value ? 'selected' : '' }}>
                        Set: {{ ucfirst($st->value) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
        </form>
    </div>
@endcan

<!-- Contract Overview & Details -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Contract Overview & Specifications</h2>
    </div>
    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div>
            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Department</div>
            <div style="font-size: 0.925rem; font-weight: 600; margin-top: 0.25rem;">{{ $tender->department_name }}</div>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Project Name</div>
            <div style="font-size: 0.925rem; margin-top: 0.25rem;">{{ $tender->project_name ?: '—' }}</div>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Pricing Mode</div>
            <div style="font-size: 0.925rem; margin-top: 0.25rem;">{{ ucfirst(str_replace('_', ' ', $tender->pricing_mode->value)) }}</div>
        </div>
        <div>
            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Contract Period</div>
            <div style="font-size: 0.925rem; margin-top: 0.25rem;">
                {{ $tender->start_date ? $tender->start_date->format('M d, Y') : 'Open' }}
                &rarr;
                {{ $tender->end_date ? $tender->end_date->format('M d, Y') : 'Ongoing' }}
            </div>
        </div>
    </div>
    @if($tender->description)
        <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Description / Scope</div>
            <div style="font-size: 0.875rem; margin-top: 0.35rem; line-height: 1.5; color: var(--text-main);">{{ $tender->description }}</div>
        </div>
    @endif
</div>

<!-- SECTION 1: SOQ Items Schedule -->
<div class="card" id="soq-items">
    <div class="card-header">
        <h2 class="card-title">Schedule of Quantities (SOQ Items)</h2>
        <span class="badge badge-neutral">{{ $tender->items->count() }} Line Items</span>
    </div>

    @if($tender->items->isEmpty())
        <div class="empty-state" style="padding: 2rem 1rem;">
            <div class="empty-title">No SOQ items recorded</div>
            <div class="empty-desc">Add plant, tree, shrub, turf, or planter specifications agreed in this tender schedule.</div>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Description</th>
                        <th>Unit</th>
                        <th>Govt Qty</th>
                        <th>Govt Rate</th>
                        <th>Quoted Rate</th>
                        <th>Final Amount</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tender->items as $item)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600;">{{ $item->item_code ?: '—' }}</td>
                            <td>
                                <div style="font-weight: 500;">{{ $item->description }}</div>
                                @if($item->product)
                                    <div style="font-size: 0.75rem; color: var(--primary-light);">Linked Product: {{ $item->product->name }}</div>
                                @endif
                            </td>
                            <td>{{ $item->unit }}</td>
                            <td>{{ number_format((float)$item->government_quantity, 2) }}</td>
                            <td>₹{{ number_format((float)$item->government_rate, 2) }}</td>
                            <td>{{ $item->quoted_rate ? '₹' . number_format((float)$item->quoted_rate, 2) : '—' }}</td>
                            <td style="font-weight: 600; color: #10b981;">
                                ₹{{ number_format((float)($item->quoted_amount ?: $item->government_amount), 2) }}
                            </td>
                            <td style="text-align: right;">
                                @can('tenders.update', 'admin')
                                    <form method="POST" action="{{ route('admin.tenders.items.destroy', [$tender, $item]) }}" onsubmit="return confirm('Remove item {{ $item->description }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Add Item Accordion -->
    @can('tenders.update', 'admin')
        <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.75rem;">+ Add SOQ Line Item</div>
            <form method="POST" action="{{ route('admin.tenders.items.store', $tender) }}">
                @csrf
                <div class="form-grid" style="grid-template-columns: 1fr 2fr 1fr 1fr 1fr 1fr;">
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Item Code</label>
                        <input type="text" name="item_code" class="form-input" placeholder="SOQ-01">
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Description / Specification</label>
                        <input type="text" name="description" class="form-input" placeholder="e.g. Royal Palm 10ft" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Unit</label>
                        <input type="text" name="unit" class="form-input" placeholder="Nos / Sqm" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Govt Qty</label>
                        <input type="number" step="0.01" name="government_quantity" class="form-input" placeholder="100" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Govt Rate (₹)</label>
                        <input type="number" step="0.01" name="government_rate" class="form-input" placeholder="500.00" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Quoted Rate (₹)</label>
                        <input type="number" step="0.01" name="quoted_rate" class="form-input" placeholder="450.00">
                    </div>
                </div>
                <div style="margin-top: 0.75rem; text-align: right;">
                    <button type="submit" class="btn btn-primary btn-sm">Add Item</button>
                </div>
            </form>
        </div>
    @endcan
</div>

<!-- SECTION 2: Requirements & Supply Calls -->
<div class="card" id="requirements">
    <div class="card-header">
        <h2 class="card-title">Site Supply Requirements (Demand Calls)</h2>
        <span class="badge badge-neutral">{{ $tender->requirements->count() }} Demands</span>
    </div>

    @if($tender->requirements->isEmpty())
        <div class="empty-state" style="padding: 2rem 1rem;">
            <div class="empty-title">No supply demands recorded yet</div>
            <div class="empty-desc">When the site engineer or department issues a supply demand letter, record it here.</div>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Requirement #</th>
                        <th>Call Date</th>
                        <th>Notes / Letter Ref</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tender->requirements as $req)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600;">#{{ $req->requirement_number }}</td>
                            <td>{{ $req->requirement_date->format('M d, Y') }}</td>
                            <td>{{ $req->notes ?: '—' }}</td>
                            <td>
                                <span class="badge badge-neutral">{{ ucfirst($req->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @can('tenders.update', 'admin')
        <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.75rem;">+ Record Supply Demand Call</div>
            <form method="POST" action="{{ route('admin.tenders.requirements.store', $tender) }}">
                @csrf
                <div class="form-grid" style="grid-template-columns: 1fr 1fr 2fr 1fr;">
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Demand Number</label>
                        <input type="text" name="requirement_number" class="form-input" placeholder="e.g. CALL-001" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Demand Date</label>
                        <input type="date" name="requirement_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Notes / Official Letter Reference</label>
                        <input type="text" name="notes" class="form-input" placeholder="e.g. Vide letter no. EE/Hort/2026/89">
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Enums\TenderRequirementStatus::cases() as $rs)
                                <option value="{{ $rs->value }}">{{ ucfirst($rs->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-top: 0.75rem; text-align: right;">
                    <button type="submit" class="btn btn-primary btn-sm">Record Demand</button>
                </div>
            </form>
        </div>
    @endcan
</div>

<!-- SECTION 3: Tender Documents -->
<div class="card" id="documents">
    <div class="card-header">
        <h2 class="card-title">Official Contract Documents</h2>
        <span class="badge badge-neutral">{{ $tender->documents->count() }} Attached</span>
    </div>

    @if($tender->documents->isEmpty())
        <div class="empty-state" style="padding: 2rem 1rem;">
            <div class="empty-title">No contract documents attached</div>
            <div class="empty-desc">Attach BOQ schedules, acceptance letters, drawings, or work orders.</div>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Uploaded Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tender->documents as $doc)
                        <tr>
                            <td><span class="badge badge-primary">{{ strtoupper(str_replace('_', ' ', $doc->document_type)) }}</span></td>
                            <td style="font-weight: 500;">{{ $doc->original_filename }}</td>
                            <td style="font-size: 0.75rem; color: var(--text-dim);">
                                {{ $doc->file_size ? number_format($doc->file_size / 1024, 1) . ' KB' : '—' }}
                            </td>
                            <td style="font-size: 0.8125rem;">{{ $doc->created_at->format('M d, Y') }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.tenders.documents.download', [$tender, $doc]) }}" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Download</a>
                                @can('tenders.update', 'admin')
                                    <form method="POST" action="{{ route('admin.tenders.documents.destroy', [$tender, $doc]) }}" onsubmit="return confirm('Delete document?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @can('tenders.update', 'admin')
        <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.75rem;">+ Upload Contract Document</div>
            <form method="POST" action="{{ route('admin.tenders.documents.store', $tender) }}" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                @csrf
                <div style="min-width: 180px;">
                    <label class="form-label required" style="font-size: 0.75rem;">Document Type</label>
                    <select name="document_type" class="form-select" required>
                        @foreach(\App\Enums\TenderDocumentType::cases() as $dt)
                            <option value="{{ $dt->value }}">{{ strtoupper(str_replace('_', ' ', $dt->value)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <label class="form-label required" style="font-size: 0.75rem;">Select File (PDF, DOC, DOCX, XLS, XLSX, Images - Max 10MB)</label>
                    <input type="file" name="document" class="form-input" style="padding: 0.4rem;" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.55rem 1rem;">Upload</button>
            </form>
        </div>
    @endcan
</div>

<!-- SECTION 4: RA Bills & Payments -->
<div class="card" id="bills">
    <div class="card-header">
        <h2 class="card-title">Running Account Bills (RA Bills)</h2>
        <span class="badge badge-neutral">{{ $tender->bills->count() }} Bills</span>
    </div>

    @if($tender->bills->isEmpty())
        <div class="empty-state" style="padding: 2rem 1rem;">
            <div class="empty-title">No RA bills generated yet</div>
            <div class="empty-desc">Generate periodic running account bills for completed supply milestones.</div>
        </div>
    @else
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Date</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tender->bills as $bill)
                        <tr>
                            <td style="font-family: monospace; font-weight: 600;">#{{ $bill->bill_number }}</td>
                            <td>{{ $bill->bill_date->format('M d, Y') }}</td>
                            <td>₹{{ number_format((float)$bill->subtotal, 2) }}</td>
                            <td>₹{{ number_format((float)$bill->tax_amount, 2) }}</td>
                            <td style="font-weight: 700; color: #10b981;">₹{{ number_format((float)$bill->grand_total, 2) }}</td>
                            <td>
                                <span class="badge badge-neutral">{{ ucfirst($bill->status->value) }}</span>
                            </td>
                            <td style="text-align: right;">
                                @can('tender-billing.update', 'admin')
                                    <form method="POST" action="{{ route('admin.tenders.bills.update-status', [$tender, $bill]) }}" style="display: inline-flex; gap: 0.25rem;">
                                        @csrf
                                        <select name="status" class="form-select" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;" onchange="this.form.submit()">
                                            @foreach(\App\Enums\TenderBillStatus::cases() as $bs)
                                                <option value="{{ $bs->value }}" {{ $bill->status->value === $bs->value ? 'selected' : '' }}>
                                                    {{ ucfirst($bs->value) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @can('tender-billing.create', 'admin')
        <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color);">
            <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.75rem;">+ Generate Running Account Bill</div>
            <form method="POST" action="{{ route('admin.tenders.bills.store', $tender) }}">
                @csrf
                <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr 1fr 1fr;">
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Bill #</label>
                        <input type="text" name="bill_number" class="form-input" placeholder="RA-01" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Bill Date</label>
                        <input type="date" name="bill_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Subtotal (₹)</label>
                        <input type="number" step="0.01" name="subtotal" class="form-input" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.75rem;">Tax (₹)</label>
                        <input type="number" step="0.01" name="tax_amount" class="form-input" placeholder="0.00">
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Grand Total (₹)</label>
                        <input type="number" step="0.01" name="grand_total" class="form-input" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="form-label required" style="font-size: 0.75rem;">Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Enums\TenderBillStatus::cases() as $bs)
                                <option value="{{ $bs->value }}">{{ ucfirst($bs->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-top: 0.75rem; text-align: right;">
                    <button type="submit" class="btn btn-primary btn-sm">Generate Bill</button>
                </div>
            </form>
        </div>
    @endcan
</div>
@endsection
