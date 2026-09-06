<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'tax_class_id',
        'name',
        'slug',
        'base_sku',
        'short_description',
        'full_description',
        'is_active',
        'is_featured',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'custom_attributes' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function tenderItems(): HasMany
    {
        return $this->hasMany(TenderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getMinPriceAttribute(): ?string
    {
        if ($this->relationLoaded('variants')) {
            $active = $this->variants->filter(fn ($v) => $v->is_active && ! $v->trashed());

            return $active->isEmpty() ? null : (string) $active->min('price');
        }

        return (string) $this->variants()->where('is_active', true)->min('price');
    }

    public function getFormattedPriceRangeAttribute(): string
    {
        if ($this->relationLoaded('variants')) {
            $active = $this->variants->filter(fn ($v) => $v->is_active && ! $v->trashed());
            if ($active->isEmpty()) {
                return '—';
            }
            $min = $active->min('price');
            $max = $active->max('price');
            if (bccomp((string) $min, (string) $max, 2) === 0) {
                return '₹'.number_format((float) $min, 2);
            }

            return '₹'.number_format((float) $min, 2).' – ₹'.number_format((float) $max, 2);
        }

        $min = $this->variants()->where('is_active', true)->min('price');
        $max = $this->variants()->where('is_active', true)->max('price');
        if ($min === null) {
            return '—';
        }
        if (bccomp((string) $min, (string) $max, 2) === 0) {
            return '₹'.number_format((float) $min, 2);
        }

        return '₹'.number_format((float) $min, 2).' – ₹'.number_format((float) $max, 2);
    }

    public function getHasStockAttribute(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->filter(fn ($v) => $v->is_active && ! $v->trashed())->contains(fn ($v) => $v->available_stock > 0);
        }

        return $this->variants()->where('is_active', true)->get()->contains(fn ($v) => $v->available_stock > 0);
    }
}
