<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'safety_stock',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'safety_stock' => 'integer',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get real-time sellable quantity.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Check if inventory is at or below safety stock threshold.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity <= $this->safety_stock;
    }

    /**
     * Scope to append available_quantity expression.
     */
    public function scopeWithAvailableQuantity($query)
    {
        return $query->selectRaw('inventories.*, GREATEST(0, quantity - reserved_quantity) as available_quantity');
    }

    /**
     * Scope to filter low-stock inventory records.
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= safety_stock');
    }
}
