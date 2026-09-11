<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BranchResolver
{
    public function forUser(User $user): ?int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        return null;
    }

    public function requiredForUser(User $user): int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        $branch = Branch::query()
            ->where('business_id', $user->business_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'No active branch found for this business.',
            ]);
        }

        return (int) $branch->id;
    }
}
