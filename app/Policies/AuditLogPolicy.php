<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->isOwner()
            && (int) $user->business_id === (int) $auditLog->business_id;
    }
}
