<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Modules\ModuleKeys;
use App\Services\BusinessModuleService;
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
        $business = $request->user()->business->load('businessModules');
        $capabilities = app(BusinessModuleService::class)->capabilityStates($business);

        return view('business.settings', compact('business', 'capabilities'));
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
            'modules.bar_shift.enabled' => 'nullable|boolean',
            'modules.catalog_variants.enabled' => 'nullable|boolean',
        ]);

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

        $business->update([
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
        ]);

        app(BusinessModuleService::class)->updateCapabilities(
            $business->fresh(),
            [
                ModuleKeys::RESTAURANT => [
                    'enabled' => $request->boolean('modules.restaurant.enabled'),
                    'use_tables' => $request->boolean('modules.restaurant.use_tables'),
                ],
                ModuleKeys::BAR_SHIFT => [
                    'enabled' => $request->boolean('modules.bar_shift.enabled'),
                ],
                ModuleKeys::CATALOG_VARIANTS => [
                    'enabled' => $request->boolean('modules.catalog_variants.enabled'),
                ],
            ],
            BusinessModuleService::SOURCE_OWNER
        );

        AuditLogger::record('business_updated', $business, $old, $business->fresh()->toArray());

        return redirect()
            ->to(tenant_route('tenant.business.edit'))
            ->with('success', 'Business profile updated.');
    }
}
