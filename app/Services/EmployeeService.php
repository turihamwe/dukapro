<?php

namespace App\Services;

use App\Enums\UserRole;
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

        if ($role === UserRole::SUPERVISOR && empty($data['branch_name'])) {
            throw ValidationException::withMessages([
                'branch_name' => 'Branch name is required for supervisors.',
            ]);
        }

        $employee = User::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $role,
            'branch_name' => $role === UserRole::SUPERVISOR ? $data['branch_name'] : null,
            'is_active' => true,
        ]);

        app(OnboardingService::class)->markEmployeesComplete($business);

        return $employee;
    }

    public function canRemove(User $actor, User $target): bool
    {
        if ($target->isOwner()) {
            return false;
        }

        if ($actor->id === $target->id && $actor->isManager()) {
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
}
