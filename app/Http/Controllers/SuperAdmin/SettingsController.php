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

        return view('superadmin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_currency_symbol' => 'required|string|max:20',
            'default_currency_position' => 'required|in:prefix,suffix',
            'support_email' => 'required|email|max:255',
            'support_phone' => 'nullable|string|max:30',
            'support_website' => 'nullable|string|max:255',
            'mpesa_api_key' => 'nullable|string|max:255',
            'airtel_api_key' => 'nullable|string|max:255',
            'mtn_api_key' => 'nullable|string|max:255',
            'maintenance_mode' => 'nullable|boolean',
        ]);

        SystemSetting::set('default_currency_symbol', $data['default_currency_symbol']);
        SystemSetting::set('default_currency_position', $data['default_currency_position']);
        SystemSetting::set('support_email', $data['support_email']);
        SystemSetting::set('support_phone', $data['support_phone'] ?? '');
        SystemSetting::set('support_website', $data['support_website'] ?? '');
        SystemSetting::set('mpesa_api_key', $data['mpesa_api_key'] ?? '');
        SystemSetting::set('airtel_api_key', $data['airtel_api_key'] ?? '');
        SystemSetting::set('mtn_api_key', $data['mtn_api_key'] ?? '');
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
