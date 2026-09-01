<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\SystemAuditLogger;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = SystemSetting::allCached();
        $yoPaymentsConfigured = app(\App\Services\YoPaymentsService::class)->isConfigured();

        return view('superadmin.settings', compact('settings', 'yoPaymentsConfigured'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_currency_symbol' => 'required|string|max:20',
            'default_currency_position' => 'required|in:prefix,suffix',
            'support_email' => 'required|email|max:255',
            'support_phone' => 'nullable|string|max:30',
            'support_website' => 'nullable|string|max:255',
            'whatsapp_float_enabled' => 'nullable|boolean',
            'mpesa_api_key' => 'nullable|string|max:255',
            'airtel_api_key' => 'nullable|string|max:255',
            'mtn_api_key' => 'nullable|string|max:255',
            'yopayments_enabled' => 'nullable|boolean',
            'yopayments_environment' => 'nullable|in:sandbox,live',
            'yopayments_api_username' => 'nullable|string|max:255',
            'yopayments_api_password' => 'nullable|string|max:255',
            'yopayments_account_id' => 'nullable|string|max:255',
            'maintenance_mode' => 'nullable|boolean',
        ]);

        SystemSetting::set('default_currency_symbol', $data['default_currency_symbol']);
        SystemSetting::set('default_currency_position', $data['default_currency_position']);
        SystemSetting::set('support_email', $data['support_email']);
        SystemSetting::set('support_phone', $data['support_phone'] ?? '');
        SystemSetting::set('support_website', $data['support_website'] ?? '');
        SystemSetting::set('whatsapp_float_enabled', $request->boolean('whatsapp_float_enabled') ? '1' : '0');
        SystemSetting::set('mpesa_api_key', $data['mpesa_api_key'] ?? '');
        SystemSetting::set('airtel_api_key', $data['airtel_api_key'] ?? '');
        SystemSetting::set('mtn_api_key', $data['mtn_api_key'] ?? '');
        SystemSetting::set('yopayments_enabled', $request->boolean('yopayments_enabled') ? '1' : '0');
        SystemSetting::set('yopayments_environment', $data['yopayments_environment'] ?? 'sandbox');
        SystemSetting::set('yopayments_api_username', $data['yopayments_api_username'] ?? '');
        if ($request->filled('yopayments_api_password')) {
            SystemSetting::set('yopayments_api_password', $data['yopayments_api_password']);
        }
        SystemSetting::set('yopayments_account_id', $data['yopayments_account_id'] ?? '');
        SystemSetting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');

        SystemAuditLogger::record(
            'settings_updated',
            'Platform settings updated by ' . $request->user()->email,
            null,
            $request->user()->id,
            ['keys' => array_keys($data)]
        );

        return back()->with('success', 'System settings saved.');
    }
}
