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
            'maintenance_mode' => 'nullable|boolean',
        ]);

        SystemSetting::set('default_currency_symbol', $data['default_currency_symbol']);
        SystemSetting::set('default_currency_position', $data['default_currency_position']);
        SystemSetting::set('support_email', $data['support_email']);
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
