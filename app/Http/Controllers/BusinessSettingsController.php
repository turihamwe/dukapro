<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Branch;
use App\Models\EfrisSetting;
use App\Services\BusinessModuleService;
use App\Services\BusinessPermissionService;
use App\Support\BatchMode;
use App\Support\EfrisCompliance;
use App\Support\VariablePricingMode;
use App\Support\WeafPlatformCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
        $this->middleware('management.access');
    }

    public function edit(Request $request)
    {
        $business = $request->user()->business->load(['businessModules', 'efrisSetting']);
        $efrisSetting = $business->efrisSetting;
        $capabilities = app(BusinessModuleService::class)->capabilityStates($business);
        $floor = app(BusinessModuleService::class)->floorSettings($business);
        $branches = $business->branches()->orderBy('name')->get();
        $permissionMatrix = app(BusinessPermissionService::class)->matrixForBusiness($business);
        $activeStaffRoles = app(BusinessPermissionService::class)->activeStaffRoles($business);

        return view('business.settings', compact('business', 'capabilities', 'floor', 'branches', 'permissionMatrix', 'activeStaffRoles', 'efrisSetting'));
    }

    public function update(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'currency_symbol' => 'required|string|max:20',
            'currency_position' => 'required|in:prefix,suffix',
            'brand_color' => 'nullable|string|max:7',
            'modules' => 'nullable|array',
            'modules.restaurant.enabled' => 'nullable|boolean',
            'modules.restaurant.use_tables' => 'nullable|boolean',
            'modules.restaurant.use_waiters' => 'nullable|boolean',
            'modules.*.enabled' => 'nullable|boolean',
            'modules.bar_shift.enabled' => 'nullable|boolean',
            'modules.catalog_variants.enabled' => 'nullable|boolean',
            'floor.use_waiters' => 'nullable|boolean',
            'floor.use_tables' => 'nullable|boolean',
            'batch_mode' => 'nullable|boolean',
            'variable_pricing_enabled' => 'nullable|boolean',
            'branch_batch_mode' => 'nullable|array',
            'role_permissions' => 'nullable|array',
            'efris.enabled' => 'nullable|boolean',
            'efris.api_token' => 'nullable|string|max:500',
            'efris.environment' => 'nullable|in:sandbox,production',
            'efris.branch_id' => 'nullable|string|max:50',
            'efris.default_buyer_tin' => 'nullable|string|max:20',
        ]);

        $efrisInput = $request->input('efris', []);
        $existingEfris = EfrisSetting::query()->firstOrNew(['business_id' => $business->id]);
        $efrisUnlocked = EfrisCompliance::isAdminUnlocked($existingEfris->exists ? $existingEfris : null);
        $efrisEnabled = $efrisUnlocked && $request->boolean('efris.enabled');

        if ($efrisEnabled) {
            $tin = preg_replace('/\D+/', '', (string) $business->tax_number);

            if ($tin === '') {
                return back()
                    ->withInput()
                    ->withErrors(['tax_number' => 'Company TIN is required when EFRIS is enabled. Add it under Tax / registration number.']);
            }

            $hasToken = $existingEfris->hasStoredToken() || ! empty($efrisInput['api_token']);
            $canAutoProvision = WeafPlatformCredentials::autoProvisionEnabled();

            if (! $hasToken && ! $canAutoProvision) {
                return back()
                    ->withInput()
                    ->withErrors(['efris_connect' => 'Connect EFRIS first, or paste a WEAF API token under Advanced settings.']);
            }

            if (! $hasToken && $canAutoProvision && ! $existingEfris->isProvisioned()) {
                return back()
                    ->withInput()
                    ->withErrors(['efris_connect' => 'Click Connect EFRIS to register your WEAF account before enabling fiscal receipts.']);
            }
        }

        $old = $business->toArray();

        $slug = $business->slug;
        if ($business->name !== $data['name']) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $counter = 1;
            while (\App\Models\Business::where('slug', $slug)->where('id', '!=', $business->id)->exists()) {
                $slug = $base . '-' . $counter++;
            }
        }

        $businessPayload = [
            'name' => $data['name'],
            'slug' => $slug,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'tax_number' => $data['tax_number'],
            'currency_symbol' => $data['currency_symbol'],
            'currency_position' => $data['currency_position'],
            'currency' => $data['currency_symbol'],
            'brand_color' => $data['brand_color'] ?? $business->brand_color,
        ];

        if (VariablePricingMode::platformEnabled()) {
            $businessPayload['variable_pricing_enabled'] = $request->boolean('variable_pricing_enabled');
        }

        $business->update($businessPayload);

        app(BusinessModuleService::class)->updateCapabilities(
            $business->fresh(),
            app(BusinessModuleService::class)->capabilitiesFromModulesInput($request->input('modules', [])),
            BusinessModuleService::SOURCE_OWNER
        );

        app(BusinessModuleService::class)->syncFloorSettings(
            $business->fresh(),
            $request->input('floor', [])
        );

        $business = $business->fresh();
        $settings = $business->settings ?? [];

        if (BatchMode::platformEnabled()) {
            $settings['batch_mode'] = $request->boolean('batch_mode');
            $business->settings = $settings;
            $business->save();
        }

        foreach ($request->input('branch_batch_mode', []) as $branchId => $value) {
            $branch = Branch::query()
                ->where('business_id', $business->id)
                ->whereKey($branchId)
                ->first();

            if (! $branch) {
                continue;
            }

            $branchSettings = $branch->settings ?? [];

            if ($value === '' || $value === null) {
                unset($branchSettings['batch_mode']);
            } else {
                $branchSettings['batch_mode'] = (bool) (int) $value;
            }

            $branch->settings = $branchSettings;
            $branch->save();
        }

        app(BusinessPermissionService::class)->syncRolePermissions(
            $business->fresh(),
            $request->input('role_permissions', []),
            app(BusinessPermissionService::class)->activeStaffRoles($business)
        );

        if ($efrisUnlocked) {
            $efrisPayload = [
                'efris_enabled' => $efrisEnabled,
                'efris_tin' => $efrisEnabled ? preg_replace('/\D+/', '', (string) $business->tax_number) : ($existingEfris->efris_tin ?? null),
                'efris_environment' => $efrisInput['environment'] ?? ($existingEfris->efris_environment ?? 'sandbox'),
                'efris_branch_id' => $efrisInput['branch_id'] ?? null,
                'default_buyer_tin' => $efrisInput['default_buyer_tin'] ?? null,
            ];

            if (! empty($efrisInput['api_token'])) {
                $efrisPayload['efris_api_token'] = $efrisInput['api_token'];
            }

            EfrisSetting::updateOrCreate(
                ['business_id' => $business->id],
                $efrisPayload
            );
        }

        AuditLogger::record('business_updated', $business, $old, $business->fresh()->toArray());

        return redirect()
            ->to(tenant_route('tenant.business.edit'))
            ->with('success', 'Business profile updated.');
    }
}
