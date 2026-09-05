<?php

namespace App\Services;

use App\Enums\BusinessType;
use App\Enums\UserRole;
use App\Models\Business;

class OnboardingService
{
    public function status(Business $business): array
    {
        $isHospitality = BusinessType::isHospitality($business->business_type);
        $hasProducts = $business->products()->exists();
        $employeesComplete = $this->employeesOnboardingComplete($business);

        return [
            'has_products' => $hasProducts,
            'employees_complete' => $employeesComplete,
            'needs_products' => ! $hasProducts,
            'needs_employees' => ! $employeesComplete,
            'is_complete' => $hasProducts && $employeesComplete,
            'sole_proprietor' => (bool) $business->sole_proprietor,
            'is_hospitality' => $isHospitality,
            'catalog_label' => $isHospitality ? 'menu items' : 'products',
            'catalog_cta' => $isHospitality ? 'Add menu items' : 'Add products',
            'catalog_route' => 'tenant.inventory.create',
            'staff_cta' => $isHospitality ? 'Set up branch staff' : 'Add employees',
            'staff_hint' => $isHospitality
                ? 'Add waiters, kitchen staff, and cashiers — each tied to a branch. Running alone? You act as cashier at the till (enable Cashier Mode) — no separate waiter staff needed.'
                : 'Create accounts for managers, supervisors, and cashiers.',
            'welcome_subtitle' => $isHospitality
                ? 'Set up your menu and branch team to start taking orders.'
                : 'Get started by adding your inventory and team. These steps stay visible until both are complete.',
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

        $moduleService = app(BusinessModuleService::class);
        $floor = $moduleService->floorSettings($business);
        $moduleService->syncFloorSettings($business->fresh(), [
            'use_waiters' => false,
            'use_tables' => $floor['use_tables'],
        ]);
    }
}
