<?php

namespace App\Models;

use App\Enums\TenderRequirementSourceType;
use App\Enums\TenderRequirementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'requirement_number',
        'requirement_date',
        'source_document_id',
        'notes',
        'status',
        'source_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requirement_date' => 'date',
            'status' => TenderRequirementStatus::class,
            'source_type' => TenderRequirementSourceType::class,
            'metadata' => 'array',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(TenderDocument::class, 'source_document_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TenderRequirementItem::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(TenderBill::class);
    }
}
