<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Scopes\BranchScope;
use App\Scopes\TenantScope;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope());

        static::creating(function (Model $model) {
            if (! $model->branch_id && BranchContext::id()) {
                $model->branch_id = BranchContext::id();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->withoutGlobalScope(BranchScope::class)
            ->where($this->getTable() . '.branch_id', $branchId);
    }

    public function scopeWithoutBranchScope($query)
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }
}
