<?php

namespace App\Models;

use App\Enums\TenderRequirementMatchingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderRequirementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_requirement_id',
        'tender_item_id',
        'product_id',
        'product_variant_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'matched_rate',
        'amount',
        'matching_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'matched_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'matching_status' => TenderRequirementMatchingStatus::class,
            'metadata' => 'array',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(TenderRequirement::class, 'tender_requirement_id');
    }

    public function tenderItem(): BelongsTo
    {
        return $this->belongsTo(TenderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(TenderBillItem::class, 'requirement_item_id');
    }
}
