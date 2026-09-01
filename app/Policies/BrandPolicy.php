<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrandPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-inventory');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('view-inventory')
            && (int) $user->business_id === (int) $brand->business_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-inventory');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('update-inventory')
            && (int) $user->business_id === (int) $brand->business_id;
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('delete-inventory')
            && (int) $user->business_id === (int) $brand->business_id;
    }
}
