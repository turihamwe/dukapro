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
        return $user->isOwner();
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->isOwner()
            && (int) $user->business_id === (int) $expense->business_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isManager();
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
