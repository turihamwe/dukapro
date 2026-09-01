<?php

namespace App\Policies;

use App\Models\SoldByUnit;
use App\Models\User;

class SoldByUnitPolicy
{
    public function create(User $user): bool
    {
        return $user->can('create-inventory');
    }
}
