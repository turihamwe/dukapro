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

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="support_phone" class="mb-1 block text-sm font-medium">Support Phone</label>
                <input type="text" name="support_phone" id="support_phone"
                       value="{{ old('support_phone', $settings['support_phone'] ?? '') }}"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
            </div>
            <div>
                <label for="support_website" class="mb-1 block text-sm font-medium">Support Website</label>
                <input type="text" name="support_website" id="support_website"
                       value="{{ old('support_website', $settings['support_website'] ?? '') }}"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <input type="hidden" name="whatsapp_float_enabled" value="0">
            <input type="checkbox" name="whatsapp_float_enabled" id="whatsapp_float_enabled" value="1"
                   {{ old('whatsapp_float_enabled', $settings['whatsapp_float_enabled'] ?? '1') === '1' ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
            <div>
                <label for="whatsapp_float_enabled" class="block text-sm font-medium text-gray-900">Floating WhatsApp support button</label>
                <p class="mt-0.5 text-xs text-gray-500">Shows a WhatsApp contact button on tenant and auth pages. Uses the support phone number above.</p>
            </div>
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

    <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-6 space-y-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">YoPayments gateway</h2>
                <p class="mt-1 text-xs text-gray-600">Collect subscription payments from business owners via Yo! Payments (Uganda). Credentials are stored securely in platform settings.</p>
            </div>
            @if($yoPaymentsConfigured)
                <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Configured</span>
            @else
                <span class="shrink-0 rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Not configured</span>
            @endif
        </div>

        <label class="flex items-start gap-3 text-sm">
            <input type="hidden" name="yopayments_enabled" value="0">
            <input type="checkbox" name="yopayments_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-violet-600"
                   {{ old('yopayments_enabled', $settings['yopayments_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
            <span>
                <span class="font-medium text-gray-900">Enable YoPayments</span>
                <span class="block text-xs text-gray-500">Use YoPayments for platform-level billing when credentials are present.</span>
            </span>
        </label>

        <div>
            <label for="yopayments_environment" class="mb-1 block text-sm font-medium">Environment</label>
            <select name="yopayments_environment" id="yopayments_environment"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
                <option value="sandbox" {{ old('yopayments_environment', $settings['yopayments_environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox / test</option>
                <option value="live" {{ old('yopayments_environment', $settings['yopayments_environment'] ?? '') === 'live' ? 'selected' : '' }}>Live production</option>
            </select>
        </div>

        <div>
            <label for="yopayments_api_username" class="mb-1 block text-sm font-medium">API username</label>
            <input type="text" name="yopayments_api_username" id="yopayments_api_username" autocomplete="off"
                   value="{{ old('yopayments_api_username', $settings['yopayments_api_username'] ?? '') }}"
                   placeholder="YoPayments API username"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        </div>

        <div>
            <label for="yopayments_api_password" class="mb-1 block text-sm font-medium">API password</label>
            <input type="password" name="yopayments_api_password" id="yopayments_api_password" autocomplete="new-password"
                   placeholder="{{ !empty($settings['yopayments_api_password']) ? '•••••••• (leave blank to keep current)' : 'YoPayments API password' }}"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:outline-none">
        </div>

        <div>
            <label for="yopayments_account_id" class="mb-1 block text-sm font-medium">Account provider code (optional)</label>
            <input type="text" name="yopayments_account_id" id="yopayments_account_id" autocomplete="off"
                   value="{{ old('yopayments_account_id', $settings['yopayments_account_id'] ?? '') }}"
                   placeholder="MTN_UGANDA or AIRTEL_UGANDA"
                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono focus:border-violet-500 focus:outline-none">
            <p class="mt-1 text-xs text-gray-500">Yo! API <code class="text-[11px]">AccountProviderCode</code> override. Leave blank to derive from MTN/Airtel selection (<code class="text-[11px]">MTN_UGANDA</code>, <code class="text-[11px]">AIRTEL_UGANDA</code>).</p>
            <p class="mt-1 text-xs text-gray-500">Sandbox API: <code class="text-[11px]">https://sandbox.yo.co.ug/services/yopaymentsdev/task.php</code> · Live: <code class="text-[11px]">https://paymentsapi1.yo.co.ug/ybs/task.php</code></p>
        </div>
    </div>

    <button type="submit" class="rounded-lg bg-violet-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
        Save Settings
    </button>
</form>
@endsection
