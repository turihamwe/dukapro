<?php

namespace App\Models\Concerns;

use App\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (! $model->business_id && TenantContext::id()) {
                $model->business_id = TenantContext::id();
            }
        });
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getTable() . '.business_id', $businessId);
    }

    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
