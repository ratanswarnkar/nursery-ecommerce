<?php

namespace App\Models;

use App\Enums\ShippingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'tracking_url',
        'carrier',
        'shipping_status',
        'shipped_at',
        'delivered_at',
        'estimated_delivery_at',
        'notes',
        'items_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'shipping_status' => ShippingStatus::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'items_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
