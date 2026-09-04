<?php

use App\Enums\TenderDocumentType;
use App\Enums\TenderRequirementSourceType;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Tender;
use App\Models\TenderBill;
use App\Models\TenderBillItem;
use App\Models\TenderDocument;
use App\Models\TenderItem;
use App\Models\TenderRequirement;
use App\Models\TenderRequirementItem;

test('13. tender can exist independently from e-commerce order', function () {
    $tender = Tender::factory()->create([
        'tender_number' => 'CPWD-2026-001',
        'name' => 'Parliament House Landscape Project',
    ]);

    expect($tender->exists)->toBeTrue()
        ->and(Order::count())->toBe(0)
        ->and($tender->tender_number)->toBe('CPWD-2026-001');
});

test('14. tender special billing flag defaults to false', function () {
    $tender = Tender::create([
        'tender_number' => 'TND-DEF-001',
        'name' => 'Standard Municipal Landscaping',
        'department_name' => 'Municipal Corporation of Delhi',
        'original_soq_value' => 1000000.00,
        'awarded_value' => 900000.00,
    ]);

    expect($tender->special_billing_enabled)->toBeFalse();
});

test('15. tender can be activated for special billing explicitly', function () {
    $tender = Tender::factory()->create([
        'special_billing_enabled' => false,
    ]);

    expect($tender->special_billing_enabled)->toBeFalse();

    $tender->update(['special_billing_enabled' => true]);

    expect($tender->fresh()->special_billing_enabled)->toBeTrue();
});

test('16. tender supports multiple SOQ items', function () {
    $tender = Tender::factory()->create();

    $item1 = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'item_code' => 'SOQ-01',
        'description' => 'Royal Palm 10-12 feet',
        'government_quantity' => 50.00,
        'government_rate' => 1200.00,
        'government_amount' => 60000.00,
    ]);

    $item2 = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'item_code' => 'SOQ-02',
        'description' => 'Duranta Gold Hedge',
        'government_quantity' => 500.00,
        'government_rate' => 45.00,
        'government_amount' => 22500.00,
    ]);

    expect($tender->items)->toHaveCount(2)
        ->and($item1->tender->id)->toBe($tender->id)
        ->and($item2->tender->id)->toBe($tender->id);
});

test('17. tender item can optionally map to catalog product and variant', function () {
    $product = Product::factory()->create(['name' => 'Bougainvillea']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'BOU-RED-10IN']);
    $tender = Tender::factory()->create();

    $tenderItem = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'description' => 'Bougainvillea Spectabilis in 10 inch pot',
    ]);

    expect($tenderItem->product->id)->toBe($product->id)
        ->and($tenderItem->productVariant->id)->toBe($variant->id);
});

test('18. tender item can exist without catalog product as standalone tender item', function () {
    $tender = Tender::factory()->create();

    $tenderItem = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'product_id' => null,
        'product_variant_id' => null,
        'item_code' => 'SPEC-RARE-01',
        'description' => 'Custom Hybrid Topiary Tree not in retail catalog',
        'government_quantity' => 10.00,
        'government_rate' => 5000.00,
        'government_amount' => 50000.00,
    ]);

    expect($tenderItem->product_id)->toBeNull()
        ->and($tenderItem->productVariant)->toBeNull()
        ->and($tenderItem->description)->toContain('Custom Hybrid Topiary Tree');
});

test('19. tender supports multiple department requirements against the same tender', function () {
    $tender = Tender::factory()->create();

    $req1 = TenderRequirement::factory()->create([
        'tender_id' => $tender->id,
        'requirement_number' => 'REQ-NORTH-01',
        'requirement_date' => '2026-03-01',
    ]);

    $req2 = TenderRequirement::factory()->create([
        'tender_id' => $tender->id,
        'requirement_number' => 'REQ-SOUTH-02',
        'requirement_date' => '2026-04-15',
    ]);

    expect($tender->requirements)->toHaveCount(2)
        ->and($req1->tender->id)->toBe($tender->id)
        ->and($req2->requirement_number)->toBe('REQ-SOUTH-02');
});

test('20. requirement supports quantities independent of original SOQ quantities', function () {
    $tender = Tender::factory()->create();

    $soqItem = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'item_code' => 'PLANT-A',
        'government_quantity' => 100.00, // Total SOQ is 100
    ]);

    $req1 = TenderRequirement::factory()->create(['tender_id' => $tender->id]);
    $req1Item = TenderRequirementItem::factory()->create([
        'tender_requirement_id' => $req1->id,
        'tender_item_id' => $soqItem->id,
        'quantity' => 25.00, // Demanded 25
    ]);

    $req2 = TenderRequirement::factory()->create(['tender_id' => $tender->id]);
    $req2Item = TenderRequirementItem::factory()->create([
        'tender_requirement_id' => $req2->id,
        'tender_item_id' => $soqItem->id,
        'quantity' => 40.00, // Demanded 40
    ]);

    expect((float) $soqItem->government_quantity)->toBe(100.00)
        ->and((float) $req1Item->quantity)->toBe(25.00)
        ->and((float) $req2Item->quantity)->toBe(40.00);
});

test('21. requirement items can reference tender items', function () {
    $tender = Tender::factory()->create();
    $soqItem = TenderItem::factory()->create(['tender_id' => $tender->id, 'item_code' => 'SOQ-99']);
    $req = TenderRequirement::factory()->create(['tender_id' => $tender->id]);

    $reqItem = TenderRequirementItem::factory()->create([
        'tender_requirement_id' => $req->id,
        'tender_item_id' => $soqItem->id,
        'item_code' => 'SOQ-99',
    ]);

    expect($reqItem->tenderItem->id)->toBe($soqItem->id)
        ->and($reqItem->requirement->id)->toBe($req->id)
        ->and($soqItem->requirementItems)->toHaveCount(1);
});

test('22. tender supports multiple bills (RA bills)', function () {
    $tender = Tender::factory()->create();

    $bill1 = TenderBill::factory()->create([
        'tender_id' => $tender->id,
        'bill_number' => 'BILL-RA-01',
        'grand_total' => 150000.00,
    ]);

    $bill2 = TenderBill::factory()->create([
        'tender_id' => $tender->id,
        'bill_number' => 'BILL-RA-02',
        'grand_total' => 220000.00,
    ]);

    expect($tender->bills)->toHaveCount(2)
        ->and($bill1->tender->id)->toBe($tender->id)
        ->and($bill2->tender->id)->toBe($tender->id);
});

test('23. tender bill preserves historical rate and description snapshot', function () {
    $tender = Tender::factory()->create();
    $soqItem = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'description' => 'Original SOQ Description',
        'government_rate' => 200.00,
    ]);

    $bill = TenderBill::factory()->create(['tender_id' => $tender->id]);

    $billItem = TenderBillItem::create([
        'tender_bill_id' => $bill->id,
        'tender_item_id' => $soqItem->id,
        'item_code' => 'SOQ-01',
        'description' => 'Snapshot Description at Billing Time',
        'unit' => 'NOS',
        'quantity' => 10.00,
        'rate' => 190.00,
        'amount' => 1900.00,
    ]);

    // Mutate the original tender item rate and description later
    $soqItem->update([
        'description' => 'Mutated Future Description',
        'government_rate' => 350.00,
    ]);

    // Verify the bill item snapshot remained intact
    $freshBillItem = $billItem->fresh();
    expect($freshBillItem->description)->toBe('Snapshot Description at Billing Time')
        ->and((float) $freshBillItem->rate)->toBe(190.00)
        ->and((float) $freshBillItem->amount)->toBe(1900.00);
});

test('24. tender billing does not require inventory or stock records', function () {
    $tender = Tender::factory()->create();
    $bill = TenderBill::factory()->create(['tender_id' => $tender->id]);

    $billItem = TenderBillItem::create([
        'tender_bill_id' => $bill->id,
        'item_code' => 'NO-INV-01',
        'description' => 'Direct Procurement Item without Warehouse Stock',
        'unit' => 'TRUCKLOAD',
        'quantity' => 5.00,
        'rate' => 15000.00,
        'amount' => 75000.00,
    ]);

    expect($billItem->exists)->toBeTrue()
        ->and(Inventory::count())->toBe(0)
        ->and(StockMovement::count())->toBe(0);
});

test('25. tender financial fields use precise decimal precision without float rounding errors', function () {
    $tender = Tender::create([
        'tender_number' => 'TND-PREC-01',
        'name' => 'High Precision Contract',
        'department_name' => 'PWD',
        'original_soq_value' => 12345678.95,
        'awarded_value' => 11234567.85,
        'below_above_percentage' => -9.0003,
    ]);

    $fresh = $tender->fresh();
    expect((string) $fresh->original_soq_value)->toBe('12345678.95')
        ->and((string) $fresh->awarded_value)->toBe('11234567.85')
        ->and((string) $fresh->below_above_percentage)->toBe('-9.0003');
});

test('26. different tenders can have different rates for the same catalog product', function () {
    $product = Product::factory()->create(['name' => 'Neem Tree 8ft']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 500.00]);

    $tender1 = Tender::factory()->create(['name' => 'Tender for Delhi Highway']);
    $tender2 = Tender::factory()->create(['name' => 'Tender for Noida Metro']);

    $t1Item = TenderItem::factory()->create([
        'tender_id' => $tender1->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quoted_rate' => 380.00,
        'final_rate' => 380.00,
    ]);

    $t2Item = TenderItem::factory()->create([
        'tender_id' => $tender2->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quoted_rate' => 420.00,
        'final_rate' => 420.00,
    ]);

    expect((float) $variant->price)->toBe(500.00)
        ->and((float) $t1Item->final_rate)->toBe(380.00)
        ->and((float) $t2Item->final_rate)->toBe(420.00);
});

test('27. item-wise quoted rate can be stored independently from government rate', function () {
    $tender = Tender::factory()->create();

    $item = TenderItem::factory()->create([
        'tender_id' => $tender->id,
        'government_rate' => 600.00,
        'quoted_rate' => 495.00,
        'calculated_rate' => 510.00,
        'final_rate' => 495.00,
    ]);

    expect((float) $item->government_rate)->toBe(600.00)
        ->and((float) $item->quoted_rate)->toBe(495.00)
        ->and((float) $item->calculated_rate)->toBe(510.00)
        ->and((float) $item->final_rate)->toBe(495.00);
});

test('28. tender documents can be associated with a tender', function () {
    $tender = Tender::factory()->create();

    $doc = TenderDocument::create([
        'tender_id' => $tender->id,
        'document_type' => TenderDocumentType::SOQ_BOQ,
        'original_filename' => 'CPWD_SOQ_Schedule_A.pdf',
        'stored_filename' => 'docs/soq_a.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024000,
    ]);

    expect($tender->documents)->toHaveCount(1)
        ->and($doc->tender->id)->toBe($tender->id)
        ->and($doc->document_type)->toBe(TenderDocumentType::SOQ_BOQ);
});

test('29. tender requirement can reference a source document', function () {
    $tender = Tender::factory()->create();

    $doc = TenderDocument::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => TenderDocumentType::DEPARTMENT_REQUIREMENT,
    ]);

    $req = TenderRequirement::create([
        'tender_id' => $tender->id,
        'requirement_number' => 'DEMAND-OCT-26',
        'requirement_date' => '2026-10-10',
        'source_document_id' => $doc->id,
        'source_type' => TenderRequirementSourceType::DOCUMENT_EXTRACTION,
    ]);

    expect($req->sourceDocument->id)->toBe($doc->id)
        ->and($doc->requirements)->toHaveCount(1);
});

test('30. tender relationships cascade or null appropriately on delete', function () {
    $tender = Tender::factory()->create();
    $doc = TenderDocument::factory()->create(['tender_id' => $tender->id]);
    $item = TenderItem::factory()->create(['tender_id' => $tender->id]);
    $req = TenderRequirement::factory()->create(['tender_id' => $tender->id, 'source_document_id' => $doc->id]);
    $reqItem = TenderRequirementItem::factory()->create([
        'tender_requirement_id' => $req->id,
        'tender_item_id' => $item->id,
    ]);
    $bill = TenderBill::factory()->create(['tender_id' => $tender->id, 'tender_requirement_id' => $req->id]);
    $billItem = TenderBillItem::factory()->create([
        'tender_bill_id' => $bill->id,
        'tender_item_id' => $item->id,
        'requirement_item_id' => $reqItem->id,
    ]);

    // Deleting the document should set source_document_id to null on requirement (nullOnDelete)
    $doc->delete();
    expect($req->fresh()->source_document_id)->toBeNull();

    // Deleting tender item should set tender_item_id to null on billItem and reqItem (nullOnDelete)
    $item->delete();
    expect($reqItem->fresh()->tender_item_id)->toBeNull()
        ->and($billItem->fresh()->tender_item_id)->toBeNull();

    // Force deleting the tender should cascade delete child records
    $tenderId = $tender->id;
    $tender->forceDelete();

    expect(TenderItem::where('tender_id', $tenderId)->count())->toBe(0)
        ->and(TenderRequirement::where('tender_id', $tenderId)->count())->toBe(0)
        ->and(TenderBill::where('tender_id', $tenderId)->count())->toBe(0);
});
