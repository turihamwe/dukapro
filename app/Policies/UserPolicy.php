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

        if ($user->isOwner()) {
            return true;
        }

        if ($user->isManager()) {
            return in_array($employee->role, ['supervisor', 'cashier'], true);
        }

        if ($user->isSupervisor()) {
            return $employee->isCashier();
        }

        return false;
    }

    public function delete(User $user, User $employee): bool
    {
        if ((int) $user->business_id !== (int) $employee->business_id) {
            return false;
        }

        return app(EmployeeService::class)->canRemove($user, $employee);
    }
}
