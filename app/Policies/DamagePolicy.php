<?php

namespace App\Policies;

use App\Models\Damage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DamagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('log-damages');
    }

    public function view(User $user, Damage $damage): bool
    {
        return $user->can('log-damages')
            && (int) $user->business_id === (int) $damage->business_id;
    }

    public function create(User $user): bool
    {
        return $user->can('log-damages');
    }
}
