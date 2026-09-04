<?php

namespace Database\Factories;

use App\Enums\TenderDocumentType;
use App\Models\Tender;
use App\Models\TenderDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenderDocumentFactory extends Factory
{
    protected $model = TenderDocument::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_type' => TenderDocumentType::SOQ_BOQ,
            'original_filename' => 'Schedule_Of_Quantities_2026.pdf',
            'stored_filename' => 'tenders/docs/'.fake()->unique()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048000,
            'metadata' => ['total_pages' => 14],
            'uploaded_by' => null,
        ];
    }
}
