<?php

namespace App\Services\Audit;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'otp',
        'code',
        'totp',
        'totp_code',
        'secret',
        'two_factor_secret',
        'recovery_code',
        'recovery_codes',
        'token',
        'cookie',
        'remember_token',
        'card',
        'card_number',
        'cvv',
        'cvc',
        'pan',
        'pin',
        'encryption_key',
        'private_key',
        'signature',
        'auth_header',
        'client_secret',
        'api_key',
    ];

    /**
     * Record an audit event for an administrator action.
     */
    public function logAdminEvent(
        string $action,
        ?Admin $admin = null,
        array $metadata = [],
        ?Model $auditable = null
    ): AuditLog {
        return AuditLog::create([
            'admin_id' => $admin?->id ?? auth('admin')->id(),
            'customer_id' => null,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : ($admin ? get_class($admin) : null),
            'auditable_id' => $auditable?->getKey() ?? $admin?->getKey(),
            'old_values' => null,
            'new_values' => $this->scrubSensitiveData($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Record an audit event for a customer action.
     */
    public function logCustomerEvent(
        string $action,
        ?Customer $customer = null,
        array $metadata = [],
        ?Model $auditable = null
    ): AuditLog {
        return AuditLog::create([
            'admin_id' => null,
            'customer_id' => $customer?->id ?? auth('customer')->id(),
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : ($customer ? get_class($customer) : null),
            'auditable_id' => $auditable?->getKey() ?? $customer?->getKey(),
            'old_values' => null,
            'new_values' => $this->scrubSensitiveData($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Record an unauthenticated / system security event.
     */
    public function logSecurityEvent(
        string $action,
        array $metadata = [],
        ?Model $auditable = null
    ): AuditLog {
        return AuditLog::create([
            'admin_id' => auth('admin')->id(),
            'customer_id' => auth('customer')->id(),
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => null,
            'new_values' => $this->scrubSensitiveData($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Recursively redact sensitive keys such as passwords, OTPs, TOTP secrets, and recovery codes.
     */
    public function scrubSensitiveData(array $data): array
    {
        $scrubbed = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $scrubbed[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $scrubbed[$key] = $this->scrubSensitiveData($value);
            } else {
                $scrubbed[$key] = $value;
            }
        }

        return $scrubbed;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
