<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_return_id',
        'payment_transaction_id',
        'amount',
        'reason',
        'status',
        'refund_reference',
        'idempotency_key',
        'gateway',
        'gateway_refund_id',
        'payload',
        'created_by_admin_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function isProcessed(): bool
    {
        return $this->status === RefundStatus::PROCESSED;
    }

    public function isPending(): bool
    {
        return $this->status === RefundStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === RefundStatus::FAILED;
    }
}
