<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\File;

class BulkProductImageImporter
{
    /**
     * Scan directory and associate matching images with products based on slug.
     *
     * @return array{matched: int, updated: int, created: int, skipped: int}
     */
    public function importFromDirectory(?string $directoryPath = null): array
    {
        $dir = $directoryPath ?: storage_path('app/public/products/catalog');

        $stats = [
            'matched' => 0,
            'updated' => 0,
            'created' => 0,
            'skipped' => 0,
        ];

        if (! File::isDirectory($dir)) {
            return $stats;
        }

        $files = File::files($dir);

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $slugCandidate = pathinfo($filename, PATHINFO_FILENAME);

            $product = Product::where('slug', $slugCandidate)->first();

            if (! $product) {
                $stats['skipped']++;

                continue;
            }

            $relativePath = 'products/catalog/'.$filename;

            // Ensure other images for this product are not primary if this is primary
            $existing = ProductImage::where('product_id', $product->id)
                ->where('file_path', $relativePath)
                ->first();

            if ($existing) {
                $existing->update([
                    'is_primary' => true,
                    'alt_text' => $existing->alt_text ?: "Lush {$product->name} nursery specimen",
                ]);
                $stats['updated']++;
            } else {
                ProductImage::create([
                    'product_id' => $product->id,
                    'file_path' => $relativePath,
                    'alt_text' => "Lush {$product->name} nursery specimen",
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);
                $stats['created']++;
            }

            $stats['matched']++;
        }

        return $stats;
    }
}
