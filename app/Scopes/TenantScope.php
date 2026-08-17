<?php

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = TenantContext::id();

        if ($businessId !== null) {
            $builder->where($model->getTable() . '.business_id', $businessId);
        }
    }
}
