<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\BusinessModuleService;
use Illuminate\Http\Request;

class BusinessModuleController extends Controller
{
    public function update(Request $request, int $businessId, BusinessModuleService $moduleService)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $businessModel = Business::query()->findOrFail($businessId);

        $data = $request->validate([
            'modules' => 'required|array',
            'modules.restaurant.enabled' => 'nullable|boolean',
            'modules.restaurant.use_tables' => 'nullable|boolean',
            'modules.restaurant.use_waiters' => 'nullable|boolean',
            'modules.*.enabled' => 'nullable|boolean',
            'modules.bar_shift.enabled' => 'nullable|boolean',
            'modules.catalog_variants.enabled' => 'nullable|boolean',
            'modules.*.billing_comped' => 'nullable|boolean',
            'billing_grandfathered' => 'nullable|boolean',
            'floor.use_waiters' => 'nullable|boolean',
            'floor.use_tables' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $before = $moduleService->capabilityStates($businessModel);

        $moduleService->updateSuperadminModules(
            $businessModel,
            $data['modules'],
            $request->boolean('billing_grandfathered'),
            $request->input('floor', [])
        );

        $after = $moduleService->capabilityStates($businessModel->fresh());

        SystemAuditLogger::record(
            'business_modules_updated',
            'Superadmin updated modules for ' . $businessModel->name,
            $businessModel->id,
            (int) $request->user()->id,
            [
                'before' => $this->compactStates($before),
                'after' => $this->compactStates($after),
                'note' => isset($data['note']) ? trim((string) $data['note']) : null,
            ]
        );

        return redirect()
            ->route('superadmin.entities.show', ['businesses', $businessModel->id, 'tab' => 'modules'])
            ->with('success', 'Business modules updated.');
    }

    /**
     * @param  array<string, array<string, mixed>>  $states
     * @return array<string, array<string, mixed>>
     */
    protected function compactStates(array $states): array
    {
        $compact = [];

        foreach ($states as $key => $state) {
            $compact[$key] = [
                'enabled' => (bool) ($state['enabled'] ?? false),
                'settings' => $state['settings'] ?? [],
            ];
        }

        return $compact;
    }
}
