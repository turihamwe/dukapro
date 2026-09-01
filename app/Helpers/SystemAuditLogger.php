<?php

namespace App\Helpers;

use App\Jobs\LogSystemAudit;
use App\Models\Business;
use App\Models\User;
use App\Support\CashierMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SystemAuditLogger
{
    public static function record(
        string $action,
        string $summary,
        ?int $businessId = null,
        ?int $userId = null,
        ?array $metadata = null
    ): void {
        $user = Auth::user();

        LogSystemAudit::dispatch([
            'business_id' => $businessId ?? optional($user)->business_id,
            'user_id' => $userId ?? optional($user)->id,
            'action' => $action,
            'summary' => $summary,
            'metadata' => self::sanitizeMetadata($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    public static function fromTenantAudit(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $businessId = null,
        ?int $userId = null
    ): void {
        $user = $userId ? User::find($userId) : Auth::user();
        $business = $businessId ? Business::find($businessId) : optional($user)->business;

        $summary = self::buildSummary($action, $user, $business, $auditable);

        $metadata = [
            'auditable_type' => $auditable ? class_basename($auditable) : null,
            'auditable_id' => $auditable ? $auditable->getKey() : null,
            'changed_fields' => self::changedFieldNames($oldValues, $newValues),
        ];

        if ($cashierContext = self::cashierContextFromValues($newValues)) {
            $metadata['cashier_context'] = $cashierContext;
        }

        self::record($action, $summary, $businessId ?? optional($business)->id, $userId ?? optional($user)->id, $metadata);
    }

    protected static function buildSummary(
        string $action,
        ?User $user,
        ?Business $business,
        ?Model $auditable
    ): string {
        $who = $user ? $user->email : 'system';

        if ($user && $user->isOwner() && CashierMode::isActive()) {
            $who .= ' (owner acting as cashier)';
        }

        $tenant = $business ? $business->name : 'platform';
        $target = $auditable ? class_basename($auditable) . ' #' . $auditable->getKey() : '';

        return trim("{$action} by {$who} @ {$tenant} {$target}");
    }

    protected static function cashierContextFromValues(?array $newValues): ?array
    {
        if (! is_array($newValues) || empty($newValues['_audit']['acting_as_cashier'])) {
            return null;
        }

        return $newValues['_audit'];
    }

    protected static function changedFieldNames(?array $old, ?array $new): array
    {
        if (! $old && ! $new) {
            return [];
        }

        return array_values(array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? []))));
    }

    protected static function sanitizeMetadata(?array $metadata): ?array
    {
        if (! $metadata) {
            return null;
        }

        unset($metadata['password'], $metadata['remember_token']);

        return $metadata;
    }
}
