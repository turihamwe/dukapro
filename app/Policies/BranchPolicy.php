<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-branches');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-branches');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('manage-branches')
            && (int) $user->business_id === (int) $branch->business_id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $this->update($user, $branch);
    }
}
