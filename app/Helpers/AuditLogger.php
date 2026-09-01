<?php

namespace App\Helpers;

use App\Models\AuditLog;
use App\Support\CashierMode;
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

        if ($newValues !== null) {
            $newValues = self::mergeCashierContext($newValues);
        } elseif ($context = self::cashierContext()) {
            $newValues = ['_audit' => $context];
        }

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

    public static function cashierContext(): ?array
    {
        $user = Auth::user();

        if (! $user || ! $user->isOwner() || ! CashierMode::isActive()) {
            return null;
        }

        return [
            'acting_as_cashier' => true,
            'performed_by_role' => 'owner',
            'performer_label' => 'Owner acting as cashier',
        ];
    }

    protected static function mergeCashierContext(array $values): array
    {
        $context = self::cashierContext();

        if (! $context) {
            return $values;
        }

        $values['_audit'] = array_merge($values['_audit'] ?? [], $context);

        return $values;
    }
}
