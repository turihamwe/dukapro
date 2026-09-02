<?php

namespace App\Scopes;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $branchId = BranchContext::id();

        if ($branchId !== null) {
            $builder->where($model->getTable() . '.branch_id', $branchId);
        }
    }
}
