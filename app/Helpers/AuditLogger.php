<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record an immutable audit log entry.
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $businessId = null,
        ?int $userId = null
    ): AuditLog {
        $user = Auth::user();

        $log = AuditLog::create([
            'business_id' => $businessId ?? optional($user)->business_id,
            'user_id' => $userId ?? optional($user)->id,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        SystemAuditLogger::fromTenantAudit(
            $action,
            $auditable,
            $oldValues,
            $newValues,
            $businessId ?? optional($user)->business_id,
            $userId ?? optional($user)->id
        );

        return $log;
    }
}
