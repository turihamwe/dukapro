<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('view-customers')
            && (int) $user->business_id === (int) $customer->business_id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage-debts');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('manage-debts')
            && (int) $user->business_id === (int) $customer->business_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->update($user, $customer);
    }
}
