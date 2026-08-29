<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isManager() || $user->isSupervisor();
    }

    public function view(User $user, Expense $expense): bool
    {
        if ((int) $user->business_id !== (int) $expense->business_id) {
            return false;
        }

        return $user->isOwner() || $user->isManager() || $user->isSupervisor();
    }

    public function create(User $user): bool
    {
        return $user->isOwner()
            || $user->isManager()
            || $user->isSupervisor()
            || $user->isCashier();
    }

    public function update(User $user, Expense $expense): bool
    {
        return ($user->isOwner() || $user->isManager())
            && (int) $user->business_id === (int) $expense->business_id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->isOwner()
            && (int) $user->business_id === (int) $expense->business_id;
    }
}
