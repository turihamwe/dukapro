<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function assignableRoles(User $actor): array
    {
        if ($actor->isOwner()) {
            return UserRole::staffRoles();
        }

        if ($actor->isManager()) {
            return [UserRole::SUPERVISOR, UserRole::CASHIER];
        }

        if ($actor->isSupervisor()) {
            return [UserRole::CASHIER];
        }

        return [];
    }

    public function create(Business $business, User $actor, array $data): User
    {
        $role = $data['role'];

        if (! in_array($role, $this->assignableRoles($actor), true)) {
            throw ValidationException::withMessages([
                'role' => 'You are not allowed to add employees with this role.',
            ]);
        }

        $branchId = $this->resolveBranchId($business, $actor, $data);

        $employee = User::create([
            'business_id' => $business->id,
            'branch_id' => $branchId,
            'name' => $data['name'],
            'username' => strtolower($data['username']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $role,
            'branch_name' => null,
            'is_active' => true,
            'ui_theme' => 'modern',
        ]);

        app(OnboardingService::class)->markEmployeesComplete($business);

        return $employee;
    }

    public function updateBranch(User $actor, User $employee, Business $business, array $data): void
    {
        $employee->branch_id = $this->resolveBranchId($business, $actor, $data, $employee);
        $employee->branch_name = null;
    }

    public function branchOptions(Business $business, User $actor): array
    {
        $query = Branch::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name');

        if ($actor->isBranchScoped() && $actor->branch_id) {
            $query->where('id', $actor->branch_id);
        }

        return $query->pluck('name', 'id')->all();
    }

    public function canRemove(User $actor, User $target): bool
    {
        if ($target->isOwner()) {
            return false;
        }

        if ($actor->id === $target->id && $actor->isManager()) {
            return false;
        }

        if ($actor->isBranchScoped() && $actor->branch_id && (int) $target->branch_id !== (int) $actor->branch_id) {
            return false;
        }

        if ($actor->isOwner()) {
            return true;
        }

        if ($actor->isManager()) {
            return in_array($target->role, [UserRole::SUPERVISOR, UserRole::CASHIER], true);
        }

        if ($actor->isSupervisor()) {
            return $target->isCashier();
        }

        return false;
    }

    protected function resolveBranchId(Business $business, User $actor, array $data, ?User $existing = null): int
    {
        if ($actor->isBranchScoped() && $actor->branch_id) {
            return (int) $actor->branch_id;
        }

        $branchId = (int) ($data['branch_id'] ?? 0);

        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => 'Please select a branch for this staff member.',
            ]);
        }

        $branch = Branch::query()
            ->where('business_id', $business->id)
            ->where('id', $branchId)
            ->where('is_active', true)
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected branch is invalid.',
            ]);
        }

        return (int) $branch->id;
    }
}
