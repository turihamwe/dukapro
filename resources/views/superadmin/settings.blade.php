@extends('layouts.superadmin')

@section('title', 'System Settings')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight">System Settings</h1>
    <p class="mt-1 text-sm text-gray-500">Global platform configuration</p>
</div>

<form method="POST" action="{{ route('superadmin.settings.update') }}" class="max-w-xl space-y-6">
    @csrf
    @method('PUT')

    <div class="rounded-xl border border-gray-200 bg-white p-6 space-y-5">
        <div>
            <label for="default_currency_symbol" class="mb-1 block text-sm font-medium">Default Currency Symbol</label>
            <input type="text" name="default_currency_symbol" id="default_currency_symbol"
                   value="{{ old('default_currency_symbol', $settings['default_currency_symbol'] ?? 'UGX') }}"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
            <p class="mt-1 text-xs text-gray-500">Applied to new business registrations</p>
        </div>

        <div>
            <label for="default_currency_position" class="mb-1 block text-sm font-medium">Currency Position</label>
            <select name="default_currency_position" id="default_currency_position"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
                <option value="prefix" {{ old('default_currency_position', $settings['default_currency_position'] ?? 'prefix') === 'prefix' ? 'selected' : '' }}>Prefix (UGX 1,000)</option>
                <option value="suffix" {{ old('default_currency_position', $settings['default_currency_position'] ?? '') === 'suffix' ? 'selected' : '' }}>Suffix (1,000 UGX)</option>
            </select>
        </div>

        <div>
            <label for="support_email" class="mb-1 block text-sm font-medium">Support Email</label>
            <input type="email" name="support_email" id="support_email"
                   value="{{ old('support_email', $settings['support_email'] ?? 'support@dukapro.com') }}"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        </div>

        <div class="flex items-start gap-3 rounded-lg border border-amber-900/50 bg-amber-950/30 p-4">
            <input type="hidden" name="maintenance_mode" value="0">
            <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1"
                   {{ old('maintenance_mode', $settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300 bg-white text-violet-600 focus:ring-violet-500">
            <div>
                <label for="maintenance_mode" class="block text-sm font-medium text-amber-200">Maintenance Mode</label>
                <p class="mt-0.5 text-xs text-amber-200/70">When enabled, tenant users see a maintenance page. SuperAdmin access is unaffected.</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 space-y-5">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Platform payment API keys</h2>
            <p class="mt-1 text-xs text-gray-500">Used for subscription billing and platform mobile-money collections — not individual store POS transactions.</p>
        </div>
        <div>
            <label for="mpesa_api_key" class="mb-1 block text-sm font-medium">M-Pesa / MTN API key</label>
            <input type="text" name="mpesa_api_key" id="mpesa_api_key"
                   value="{{ old('mpesa_api_key', $settings['mpesa_api_key'] ?? '') }}"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        </div>
        <div>
            <label for="airtel_api_key" class="mb-1 block text-sm font-medium">Airtel Money API key</label>
            <input type="text" name="airtel_api_key" id="airtel_api_key"
                   value="{{ old('airtel_api_key', $settings['airtel_api_key'] ?? '') }}"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        </div>
        <div>
            <label for="mtn_api_key" class="mb-1 block text-sm font-medium">MTN MoMo API key</label>
            <input type="text" name="mtn_api_key" id="mtn_api_key"
                   value="{{ old('mtn_api_key', $settings['mtn_api_key'] ?? '') }}"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        </div>
    </div>

    <button type="submit" class="rounded-lg bg-violet-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
        Save Settings
    </button>
</form>
@endsection
