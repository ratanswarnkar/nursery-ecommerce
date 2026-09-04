<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderBillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_bill_id',
        'tender_item_id',
        'requirement_item_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'rate',
        'amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(TenderBill::class, 'tender_bill_id');
    }

    public function tenderItem(): BelongsTo
    {
        return $this->belongsTo(TenderItem::class);
    }

    public function requirementItem(): BelongsTo
    {
        return $this->belongsTo(TenderRequirementItem::class, 'requirement_item_id');
    }
}
