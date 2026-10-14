<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class BranchImpersonation
{
    public const SESSION_KEY = 'impersonated_branch_id';

    public static function canSwitch(?User $user): bool
    {
        if (! $user || ! $user->business_id) {
            return false;
        }

        if (! Gate::forUser($user)->allows('impersonate-branches')) {
            return false;
        }

        return static::switchableBranches($user)->isNotEmpty();
    }

    public static function isActive(): bool
    {
        return static::branchId() !== null;
    }

    public static function branchId(): ?int
    {
        $stored = session(static::SESSION_KEY);

        if ($stored === null || $stored === '') {
            return null;
        }

        return (int) $stored;
    }

    public static function branch(?User $user = null): ?Branch
    {
        $user = $user ?? auth()->user();
        $branchId = static::branchId();

        if (! $user || ! $branchId) {
            return null;
        }

        return Branch::query()
            ->where('business_id', (int) $user->business_id)
            ->where('is_active', true)
            ->whereKey($branchId)
            ->first();
    }

    /**
     * @return Collection<int, Branch>
     */
    public static function switchableBranches(User $user): Collection
    {
        return Branch::query()
            ->where('business_id', (int) $user->business_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public static function enter(User $user, Branch $branch): void
    {
        if (! Gate::forUser($user)->allows('impersonate-branches')) {
            abort(403);
        }

        if ((int) $branch->business_id !== (int) $user->business_id || ! $branch->is_active) {
            abort(404);
        }

        session([static::SESSION_KEY => (int) $branch->id]);
    }

    public static function leave(): void
    {
        session()->forget(static::SESSION_KEY);
    }

    public static function validatedBranchId(User $user): ?int
    {
        if (! static::branchId()) {
            return null;
        }

        $branch = static::branch($user);

        if (! $branch) {
            static::leave();

            return null;
        }

        if (! Gate::forUser($user)->allows('impersonate-branches')) {
            static::leave();

            return null;
        }

        return (int) $branch->id;
    }
}
