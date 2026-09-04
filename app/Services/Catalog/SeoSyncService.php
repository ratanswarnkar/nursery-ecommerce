<?php

namespace App\Services\Catalog;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;

class SeoSyncService
{
    /**
     * Synchronize polymorphic SEO metadata for a model.
     */
    public function syncSeo(Model $model, array $data): ?SeoMetadata
    {
        $metaTitle = trim((string) ($data['meta_title'] ?? ''));
        $metaDescription = trim((string) ($data['meta_description'] ?? ''));
        $metaKeywords = trim((string) ($data['meta_keywords'] ?? ''));
        $canonicalUrl = trim((string) ($data['canonical_url'] ?? ''));

        $hasContent = $metaTitle !== '' || $metaDescription !== '' || $metaKeywords !== '' || $canonicalUrl !== '';

        if (! $hasContent) {
            $model->seoMetadata()->delete();

            return null;
        }

        return $model->seoMetadata()->updateOrCreate(
            [
                'seoable_type' => get_class($model),
                'seoable_id' => $model->getKey(),
            ],
            [
                'meta_title' => $metaTitle ?: null,
                'meta_description' => $metaDescription ?: null,
                'meta_keywords' => $metaKeywords ?: null,
                'canonical_url' => $canonicalUrl ?: null,
            ]
        );
    }
}
