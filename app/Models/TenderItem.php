<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'product_id',
        'product_variant_id',
        'item_code',
        'description',
        'unit',
        'government_quantity',
        'government_rate',
        'government_amount',
        'quoted_rate',
        'quoted_amount',
        'calculated_rate',
        'final_rate',
        'metadata',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'government_quantity' => 'decimal:2',
            'government_rate' => 'decimal:2',
            'government_amount' => 'decimal:2',
            'quoted_rate' => 'decimal:2',
            'quoted_amount' => 'decimal:2',
            'calculated_rate' => 'decimal:2',
            'final_rate' => 'decimal:2',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function requirementItems(): HasMany
    {
        return $this->hasMany(TenderRequirementItem::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(TenderBillItem::class);
    }
}
