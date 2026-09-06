<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'cost_price',
        'weight',
        'length',
        'width',
        'height',
        'is_active',
        'is_default',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'custom_attributes' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values')
            ->withTimestamps();
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tenderItems(): HasMany
    {
        return $this->hasMany(TenderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')->orderBy('sort_order');
    }

    /**
     * Get total available stock across all active warehouses.
     */
    public function getAvailableStockAttribute(): int
    {
        return (int) $this->inventories()
            ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
            ->sum(DB::raw('GREATEST(0, quantity - reserved_quantity)'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if ($this->compare_at_price && bccomp((string) $this->compare_at_price, (string) $this->price, 2) > 0) {
            $diff = bcsub((string) $this->compare_at_price, (string) $this->price, 2);
            $ratio = bcdiv($diff, (string) $this->compare_at_price, 4);

            return (int) round((float) bcmul($ratio, '100', 2));
        }

        return null;
    }
}
