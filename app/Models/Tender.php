<?php

namespace App\Models;

use App\Enums\TenderPricingMode;
use App\Enums\TenderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tender extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'special_billing_enabled' => false,
        'status' => TenderStatus::DRAFT,
        'pricing_mode' => TenderPricingMode::ITEM_WISE,
    ];

    protected $fillable = [
        'tender_number',
        'name',
        'department_name',
        'project_name',
        'description',
        'original_soq_value',
        'awarded_value',
        'below_above_percentage',
        'pricing_mode',
        'status',
        'start_date',
        'end_date',
        'special_billing_enabled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'original_soq_value' => 'decimal:2',
            'awarded_value' => 'decimal:2',
            'below_above_percentage' => 'decimal:4',
            'pricing_mode' => TenderPricingMode::class,
            'status' => TenderStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'special_billing_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenderDocument::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenderItem::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(TenderRequirement::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(TenderBill::class);
    }
}
