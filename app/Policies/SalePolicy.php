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

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'cashier'], true);
    }
}
