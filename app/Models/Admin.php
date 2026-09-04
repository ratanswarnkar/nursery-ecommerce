<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'auth_token_version',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'auth_token_version' => 'integer',
            'two_factor_confirmed_at' => 'datetime',
            'recovery_codes' => 'array',
        ];
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(AdminLoginActivity::class);
    }
}
