<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;

class OnboardingService
{
    public function status(Business $business): array
    {
        $hasProducts = $business->products()->exists();
        $employeesComplete = $this->employeesOnboardingComplete($business);

        return [
            'has_products' => $hasProducts,
            'employees_complete' => $employeesComplete,
            'needs_products' => ! $hasProducts,
            'needs_employees' => ! $employeesComplete,
            'is_complete' => $hasProducts && $employeesComplete,
            'sole_proprietor' => (bool) $business->sole_proprietor,
        ];
    }

    public function employeesOnboardingComplete(Business $business): bool
    {
        if ($business->employees_onboarding_complete || $business->sole_proprietor) {
            return true;
        }

        return $business->users()
            ->where('role', '!=', UserRole::OWNER)
            ->exists();
    }

    public function markEmployeesComplete(Business $business): void
    {
        $business->update(['employees_onboarding_complete' => true]);
    }

    public function markSoleProprietor(Business $business): void
    {
        $business->update([
            'sole_proprietor' => true,
            'employees_onboarding_complete' => true,
        ]);
    }
}
