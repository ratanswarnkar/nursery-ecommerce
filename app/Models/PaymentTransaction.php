<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_number',
        'gateway',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'idempotency_key',
        'failure_code',
        'failure_message',
        'payload',
        'paid_at',
        'failed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'payload' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentStatus::CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::EXPIRED;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }
}
