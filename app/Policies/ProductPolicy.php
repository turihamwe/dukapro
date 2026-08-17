<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isManager();
    }

    public function view(User $user, Product $product): bool
    {
        return ($user->isOwner() || $user->isManager())
            && (int) $user->business_id === (int) $product->business_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isManager();
    }

    public function update(User $user, Product $product): bool
    {
        return ($user->isOwner() || $user->isManager())
            && (int) $user->business_id === (int) $product->business_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isOwner()
            && (int) $user->business_id === (int) $product->business_id;
    }

    public function viewCostPrice(User $user): bool
    {
        return $user->isOwner() || $user->isManager();
    }
}
