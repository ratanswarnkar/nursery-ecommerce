<?php

namespace App\Models;

use App\Enums\TenderBillStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'tender_requirement_id',
        'bill_number',
        'bill_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'status',
        'notes',
        'source_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'status' => TenderBillStatus::class,
            'metadata' => 'array',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(TenderRequirement::class, 'tender_requirement_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenderBillItem::class);
    }
}
