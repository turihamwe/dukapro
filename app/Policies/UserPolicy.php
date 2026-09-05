<?php

namespace App\Policies;

use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage-employees');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-employees');
    }

    public function update(User $user, User $employee): bool
    {
        if ((int) $user->business_id !== (int) $employee->business_id || $employee->isOwner()) {
            return false;
        }

        if ($user->isBranchScoped() && $user->branch_id && (int) $employee->branch_id !== (int) $user->branch_id) {
            return false;
        }

        return app(EmployeeService::class)->canManage($user, $employee);
    }

    public function delete(User $user, User $employee): bool
    {
        if ((int) $user->business_id !== (int) $employee->business_id) {
            return false;
        }

        return app(EmployeeService::class)->canRemove($user, $employee);
    }
}
