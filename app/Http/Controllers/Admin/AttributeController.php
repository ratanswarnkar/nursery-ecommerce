<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeRequest;
use App\Models\Attribute;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $attributes = Attribute::withCount('values')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        $types = AttributeType::cases();

        return view('admin.attributes.create', compact('types'));
    }

    public function store(AttributeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $attribute = Attribute::create([
            'name' => $data['name'],
            'code' => strtolower(trim($data['code'])),
            'type' => $data['type'],
            'is_required' => $data['is_required'] ?? false,
            'is_filterable' => $data['is_filterable'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->auditLogger->logAdminEvent(
            'catalog.attribute_created',
            auth('admin')->user(),
            ['id' => $attribute->id, 'name' => $attribute->name, 'code' => $attribute->code],
            $attribute
        );

        return redirect()->route('admin.attributes.index')
            ->with('success', "Attribute '{$attribute->name}' created successfully.");
    }

    public function edit(Attribute $attribute): View
    {
        $types = AttributeType::cases();

        return view('admin.attributes.edit', compact('attribute', 'types'));
    }

    public function update(AttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $data = $request->validated();

        $attribute->update([
            'name' => $data['name'],
            'code' => strtolower(trim($data['code'])),
            'type' => $data['type'],
            'is_required' => $data['is_required'] ?? false,
            'is_filterable' => $data['is_filterable'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->auditLogger->logAdminEvent(
            'catalog.attribute_updated',
            auth('admin')->user(),
            ['id' => $attribute->id, 'changed' => $attribute->getChanges()],
            $attribute
        );

        return redirect()->route('admin.attributes.index')
            ->with('success', "Attribute '{$attribute->name}' updated successfully.");
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $isUsedInVariants = $attribute->values()->whereHas('variants')->exists();

        if ($isUsedInVariants) {
            return redirect()->route('admin.attributes.index')
                ->with('error', 'Cannot delete attribute currently linked to product variants. Remove it from variants first.');
        }

        $name = $attribute->name;
        $id = $attribute->id;

        $attribute->values()->delete();
        $attribute->delete();

        $this->auditLogger->logAdminEvent(
            'catalog.attribute_deleted',
            auth('admin')->user(),
            ['id' => $id, 'name' => $name],
            $attribute
        );

        return redirect()->route('admin.attributes.index')
            ->with('success', "Attribute '{$name}' deleted successfully.");
    }
}
