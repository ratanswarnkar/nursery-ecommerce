<?php

namespace App\Services\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaStorageService
{
    private const ALLOWED_MIMES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/avif' => ['avif'],
    ];

    /**
     * Upload and store a product image securely.
     */
    public function storeProductImage(UploadedFile $file, int $productId): string
    {
        $this->validateImageBinary($file, 5120);

        $extension = $this->determineSafeExtension($file);
        $filename = Str::uuid().'.'.$extension;
        $directory = 'products/'.$productId;

        $path = $file->storeAs($directory, $filename, 'public');

        if (! $path) {
            throw new \RuntimeException('Failed to store product image file.');
        }

        return $path;
    }

    /**
     * Upload and store a brand logo securely.
     */
    public function storeBrandLogo(UploadedFile $file): string
    {
        $this->validateImageBinary($file, 2048);

        $extension = $this->determineSafeExtension($file);
        $filename = Str::uuid().'.'.$extension;
        $directory = 'brands';

        $path = $file->storeAs($directory, $filename, 'public');

        if (! $path) {
            throw new \RuntimeException('Failed to store brand logo file.');
        }

        return $path;
    }

    /**
     * Delete a stored file from public storage.
     */
    public function deleteFile(?string $filePath): bool
    {
        if (! $filePath) {
            return false;
        }

        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        return false;
    }

    /**
     * Validate binary content via fileinfo and check size limits.
     */
    public function validateImageBinary(UploadedFile $file, int $maxKilobytes): void
    {
        // 1. File size check
        $sizeKb = $file->getSize() / 1024;
        if ($sizeKb > $maxKilobytes) {
            throw ValidationException::withMessages([
                'image' => ["The image must not exceed {$maxKilobytes} kilobytes."],
            ]);
        }

        // 2. Binary inspection via fileinfo
        $mime = $file->getMimeType();
        if (! array_key_exists($mime, self::ALLOWED_MIMES)) {
            throw ValidationException::withMessages([
                'image' => ['The uploaded file is not a supported image format. Only JPEG, PNG, WebP, and AVIF are allowed. SVG and executable formats are strictly prohibited.'],
            ]);
        }

        // 3. Client extension verification against mime
        $clientExt = strtolower($file->getClientOriginalExtension());
        if (! in_array($clientExt, self::ALLOWED_MIMES[$mime], true)) {
            throw ValidationException::withMessages([
                'image' => ['The file extension does not match its detected MIME type.'],
            ]);
        }

        // 4. Double extension check
        $clientFilename = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|phar|sh|exe|pl|cgi|js|html|htm)\./i', $clientFilename)) {
            throw ValidationException::withMessages([
                'image' => ['Disguised executable extensions are strictly forbidden.'],
            ]);
        }
    }

    private function determineSafeExtension(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => throw new \InvalidArgumentException('Unsupported MIME type: '.$mime),
        };
    }
}
