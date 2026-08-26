<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
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
        $business = $request->user()->business;

        return view('business.settings', compact('business'));
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
            'logo' => 'nullable|image|max:2048',
            'mpesa_api_key' => 'nullable|string|max:255',
            'airtel_api_key' => 'nullable|string|max:255',
            'mtn_api_key' => 'nullable|string|max:255',
        ]);

        $old = $business->toArray();

        $settings = $business->settings ?? [];
        $settings['mpesa_api_key'] = $data['mpesa_api_key'] ?? null;
        $settings['airtel_api_key'] = $data['airtel_api_key'] ?? null;
        $settings['mtn_api_key'] = $data['mtn_api_key'] ?? null;

        $slug = $business->slug;
        if ($business->name !== $data['name']) {
            $base = Str::slug($data['name']);
            $slug = $base;
            $counter = 1;
            while (\App\Models\Business::where('slug', $slug)->where('id', '!=', $business->id)->exists()) {
                $slug = $base . '-' . $counter++;
            }
        }

        $update = [
            'name' => $data['name'],
            'slug' => $slug,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'tax_number' => $data['tax_number'],
            'currency_symbol' => $data['currency_symbol'],
            'currency_position' => $data['currency_position'],
            'brand_color' => $data['brand_color'] ?? $business->brand_color,
            'settings' => $settings,
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('business-logos/' . $business->id, 'public');
            $update['logo_path'] = $path;
        }

        $business->update($update);

        AuditLogger::record('business_updated', $business, $old, $business->fresh()->toArray());

        return redirect()
            ->to(tenant_route('tenant.business.edit'))
            ->with('success', 'Business profile updated.');
    }
}
