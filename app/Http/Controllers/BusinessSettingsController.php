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
            'use_product_variants' => 'nullable|boolean',
            'shift_waiter_mode' => 'nullable|boolean',
        ]);

        $old = $business->toArray();

        $settings = $business->settings ?? [];
        $settings['use_product_variants'] = $request->boolean('use_product_variants');
        $settings['shift_waiter_mode'] = $request->boolean('shift_waiter_mode');

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
            'settings' => $settings,
        ]);

        AuditLogger::record('business_updated', $business, $old, $business->fresh()->toArray());

        return redirect()
            ->to(tenant_route('tenant.business.edit'))
            ->with('success', 'Business profile updated.');
    }
}
