<?php

namespace App\Models;

use App\Enums\TenderDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'document_type',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'metadata',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => TenderDocumentType::class,
            'file_size' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(TenderRequirement::class, 'source_document_id');
    }
}
