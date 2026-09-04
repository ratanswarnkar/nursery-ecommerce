<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'ip_address',
        'user_agent',
        'login_at',
        'is_successful',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'is_successful' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
