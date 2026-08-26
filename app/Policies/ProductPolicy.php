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
        return $user->can('view-inventory');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('view-inventory')
            && (int) $user->business_id === (int) $product->business_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-inventory');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('update-inventory')
            && (int) $user->business_id === (int) $product->business_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('delete-inventory')
            && (int) $user->business_id === (int) $product->business_id;
    }

    public function viewCostPrice(User $user): bool
    {
        return $user->can('view-cost-prices');
    }
}
