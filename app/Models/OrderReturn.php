<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'quantity',
        'reason',
        'status',
        'refund_amount',
        'admin_notes',
        'received_at',
        'approved_at',
        'rejected_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => ReturnStatus::class,
            'refund_amount' => 'decimal:2',
            'received_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function isRequested(): bool
    {
        return $this->status === ReturnStatus::REQUESTED;
    }

    public function isApproved(): bool
    {
        return $this->status === ReturnStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ReturnStatus::REJECTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === ReturnStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ReturnStatus::CANCELLED;
    }
}
