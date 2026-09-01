@extends('layouts.admin')

@section('title', 'Business Profile')

@section('content')
@php $settings = $business->settings ?? []; @endphp

<x-page-header title="Business Profile" subtitle="Update your store information" />

<div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
    <p class="font-semibold">Staff login portal</p>
    <p class="mt-1 break-all text-indigo-800">{{ $business->portalLoginUrl() }}</p>
    <p class="mt-2 text-xs text-indigo-700">Share this private URL with your team.</p>
</div>

<x-card class="max-w-3xl">
    <form method="POST" action="{{ tenant_route('tenant.business.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-input type="text" name="name" label="Business name" value="{{ old('name', $business->name) }}" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="email" name="email" label="Business email" value="{{ old('email', $business->email) }}" />
            <x-input type="text" name="phone" label="Phone" value="{{ old('phone', $business->phone) }}" />
        </div>
        <x-textarea name="address" label="Address" rows="2">{{ old('address', $business->address) }}</x-textarea>
        <x-input type="text" name="tax_number" label="Tax / registration number" value="{{ old('tax_number', $business->tax_number) }}" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input type="text" name="currency_symbol" label="Currency symbol" value="{{ old('currency_symbol', $business->currency_symbol ?? 'UGX') }}" required />
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Currency position</label>
                <select name="currency_position" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="prefix" @selected(old('currency_position', $business->currency_position) === 'prefix')>Prefix (UGX 100)</option>
                    <option value="suffix" @selected(old('currency_position', $business->currency_position) === 'suffix')>Suffix (100/=)</option>
                </select>
            </div>
        </div>

        <x-input type="color" name="brand_color" label="Brand color" value="{{ old('brand_color', $business->brand_color ?? '#4f46e5') }}" />

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="use_product_variants" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    {{ old('use_product_variants', $settings['use_product_variants'] ?? false) ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Default new products to variant mode</span>
                    <span class="mt-1 block text-xs text-gray-500">Optional shortcut — you can still toggle variants on/off for each product individually on the add/edit form.</span>
                </span>
            </label>
        </div>

        <x-button type="submit" variant="primary">Save business profile</x-button>
    </form>
</x-card>
@endsection
