<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeValueRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttributeValueController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(Attribute $attribute): View
    {
        $values = $attribute->values()
            ->withCount('variants')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('admin.attributes.values.index', compact('attribute', 'values'));
    }

    public function store(AttributeValueRequest $request, Attribute $attribute): RedirectResponse
    {
        $data = $request->validated();

        $value = $attribute->values()->create([
            'value' => trim($data['value']),
            'label' => trim($data['label']),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->auditLogger->logAdminEvent(
            'catalog.attribute_value_created',
            auth('admin')->user(),
            ['id' => $value->id, 'attribute_id' => $attribute->id, 'value' => $value->value, 'label' => $value->label],
            $value
        );

        return redirect()->route('admin.attributes.values.index', $attribute)
            ->with('success', "Value '{$value->label}' added successfully.");
    }

    public function update(AttributeValueRequest $request, Attribute $attribute, AttributeValue $value): RedirectResponse
    {
        abort_unless($value->attribute_id === $attribute->id, 404);

        $data = $request->validated();

        $value->update([
            'value' => trim($data['value']),
            'label' => trim($data['label']),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->auditLogger->logAdminEvent(
            'catalog.attribute_value_updated',
            auth('admin')->user(),
            ['id' => $value->id, 'attribute_id' => $attribute->id, 'changed' => $value->getChanges()],
            $value
        );

        return redirect()->route('admin.attributes.values.index', $attribute)
            ->with('success', "Value '{$value->label}' updated successfully.");
    }

    public function destroy(Attribute $attribute, AttributeValue $value): RedirectResponse
    {
        abort_unless($value->attribute_id === $attribute->id, 404);

        if ($value->variants()->exists()) {
            return redirect()->route('admin.attributes.values.index', $attribute)
                ->with('error', 'Cannot delete attribute value currently assigned to product variants. Remove from variants first.');
        }

        $id = $value->id;
        $label = $value->label;

        $value->delete();

        $this->auditLogger->logAdminEvent(
            'catalog.attribute_value_deleted',
            auth('admin')->user(),
            ['id' => $id, 'attribute_id' => $attribute->id, 'label' => $label],
            $attribute
        );

        return redirect()->route('admin.attributes.values.index', $attribute)
            ->with('success', "Value '{$label}' deleted successfully.");
    }
}
