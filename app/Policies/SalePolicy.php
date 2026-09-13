<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isManager();
    }

    public function view(User $user, Sale $sale): bool
    {
        return ($user->isOwner() || $user->isManager())
            && (int) $user->business_id === (int) $sale->business_id;
    }

    public function viewReceipt(User $user, Sale $sale): bool
    {
        if ((int) $user->business_id !== (int) $sale->business_id) {
            return false;
        }

        if ($user->isOwner() || $user->isManager()) {
            return true;
        }

        if (! $user->can('access-pos')) {
            return false;
        }

        if ($user->isBranchScoped() && $user->branch_id) {
            return (int) $sale->branch_id === (int) $user->branch_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'supervisor', 'cashier'], true);
    }
}
