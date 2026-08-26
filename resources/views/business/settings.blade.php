@extends('layouts.admin')

@section('title', 'Business Profile')

@section('content')
@php $settings = $business->settings ?? []; @endphp

<x-page-header title="Business Profile" subtitle="Update your store information and integrations" />

<div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
    <p class="font-semibold">Staff login portal</p>
    <p class="mt-1 break-all text-indigo-800">{{ $business->portalLoginUrl() }}</p>
    <p class="mt-2 text-xs text-indigo-700">Share this private URL with your team. It displays your logo and business name on sign in.</p>
</div>

<x-card class="max-w-3xl">
    <form method="POST" action="{{ tenant_route('tenant.business.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Logo</label>
                @if($business->logoUrl())
                    <img src="{{ $business->logoUrl() }}" alt="" class="mb-2 h-16 w-auto rounded-lg border border-gray-200">
                @endif
                <input type="file" name="logo" accept="image/*" class="block w-full text-sm">
            </div>
            <x-input type="color" name="brand_color" label="Brand color" value="{{ old('brand_color', $business->brand_color ?? '#4f46e5') }}" />
        </div>

        <x-input type="text" name="name" label="Business name" value="{{ old('name', $business->name) }}" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="email" name="email" label="Business email" value="{{ old('email', $business->email) }}" />
            <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $business->phone) }}" />
        </div>
        <x-textarea name="address" label="Address" rows="2">{{ old('address', $business->address) }}</x-textarea>
        <x-input type="text" name="tax_number" label="Tax / registration number" value="{{ old('tax_number', $business->tax_number) }}" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="text" name="currency_symbol" label="Currency symbol" value="{{ old('currency_symbol', $business->currency_symbol) }}" required />
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Currency position</label>
                <select name="currency_position" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="prefix" @selected(old('currency_position', $business->currency_position) === 'prefix')>Prefix (UGX 100)</option>
                    <option value="suffix" @selected(old('currency_position', $business->currency_position) === 'suffix')>Suffix (100/=)</option>
                </select>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <h3 class="text-sm font-semibold text-gray-900">API keys & integrations</h3>
            <p class="mt-1 text-xs text-gray-500">Store payment provider credentials securely for mobile money and POS integrations.</p>
            <div class="mt-4 space-y-4">
                <x-input type="text" name="mpesa_api_key" label="M-Pesa / MTN API key" value="{{ old('mpesa_api_key', $settings['mpesa_api_key'] ?? '') }}" />
                <x-input type="text" name="airtel_api_key" label="Airtel Money API key" value="{{ old('airtel_api_key', $settings['airtel_api_key'] ?? '') }}" />
                <x-input type="text" name="mtn_api_key" label="MTN MoMo API key" value="{{ old('mtn_api_key', $settings['mtn_api_key'] ?? '') }}" />
            </div>
        </div>

        <x-button type="submit" variant="primary">Save business profile</x-button>
    </form>
</x-card>
@endsection
