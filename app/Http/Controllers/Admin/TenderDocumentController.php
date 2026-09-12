<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenderDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Models\TenderDocument;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenderDocumentController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Upload and attach a document to a tender.
     */
    public function store(Request $request, Tender $tender): RedirectResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
            'document_type' => ['required', new Enum(TenderDocumentType::class)],
        ]);

        $file = $request->file('document');
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        $path = $file->store("tenders/{$tender->id}", 'local');

        $doc = TenderDocument::create([
            'tender_id' => $tender->id,
            'document_type' => $request->document_type,
            'original_filename' => $originalFilename,
            'stored_filename' => $path,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => auth('admin')->id(),
        ]);

        $this->auditLogger->logAdminEvent('tender_document.uploaded', auth('admin')->user(), [
            'tender_id' => $tender->id,
            'filename' => $originalFilename,
        ], $doc);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "Document {$originalFilename} uploaded successfully.");
    }

    /**
     * Safely download an authorized tender document.
     */
    public function download(Tender $tender, TenderDocument $document): StreamedResponse
    {
        abort_unless((int) $document->tender_id === (int) $tender->id, 404);

        if (! Storage::disk('local')->exists($document->stored_filename)) {
            abort(404, 'Document file not found on disk.');
        }

        return Storage::disk('local')->download(
            $document->stored_filename,
            $document->original_filename,
            ['Content-Type' => $document->mime_type ?: 'application/octet-stream']
        );
    }

    /**
     * Delete a tender document.
     */
    public function destroy(Tender $tender, TenderDocument $document): RedirectResponse
    {
        abort_unless((int) $document->tender_id === (int) $tender->id, 404);

        if (Storage::disk('local')->exists($document->stored_filename)) {
            Storage::disk('local')->delete($document->stored_filename);
        }

        $filename = $document->original_filename;
        $document->delete();

        $this->auditLogger->logAdminEvent('tender_document.deleted', auth('admin')->user(), [
            'filename' => $filename,
        ], $tender);

        return redirect()->route('admin.tenders.show', $tender)
            ->with('success', "Document {$filename} deleted.");
    }
}
