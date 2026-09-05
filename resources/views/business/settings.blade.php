@extends('layouts.admin')

@section('title', 'Business Profile')

@section('content')
@php
    use App\Enums\BusinessType;
    use App\Modules\ModuleKeys;

    $businessTypeLabel = BusinessType::label($business->business_type);
@endphp

<x-page-header title="Business Profile" subtitle="Update your store information and capabilities" />

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

        <div class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5">
            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-900">Capabilities</p>
                <p class="mt-1 text-xs text-gray-500">
                    Turn features on or off for this business. You signed up as <span class="font-medium text-gray-700">{{ $businessTypeLabel }}</span> — suggested modules are marked below, but you can enable any combination.
                </p>
            </div>

            <div class="space-y-4">
                @php
                    $restaurant = $capabilities[ModuleKeys::RESTAURANT] ?? [];
                    $restaurantEnabled = (bool) old('modules.restaurant.enabled', $restaurant['enabled'] ?? false);
                    $tablesEnabled = (bool) old('modules.restaurant.use_tables', $restaurant['settings']['use_tables'] ?? false);
                @endphp
                <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 space-y-4">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="modules[restaurant][enabled]" value="0">
                        <input type="checkbox" name="modules[restaurant][enabled]" value="1"
                               class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               {{ $restaurantEnabled ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ $restaurant['label'] ?? 'Restaurant Mode' }}</span>
                            <span class="mt-1 block text-xs text-gray-600">{{ $restaurant['description'] ?? '' }}</span>
                            @if($restaurant['suggested'] ?? false)
                                <span class="mt-1 inline-block rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-orange-800">Suggested for {{ $businessTypeLabel }}</span>
                            @endif
                        </span>
                    </label>
                    <label class="flex items-start gap-3 border-t border-orange-200/80 pt-4">
                        <input type="hidden" name="modules[restaurant][use_tables]" value="0">
                        <input type="checkbox" name="modules[restaurant][use_tables]" value="1"
                               class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               {{ $tablesEnabled ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Use restaurant tables</span>
                            <span class="mt-1 block text-xs text-gray-600">Manage tables and tie orders to seating. Leave off for bars or counter-service venues.</span>
                            @can('manage-restaurant-tables')
                                @if($restaurantEnabled)
                                    <a href="{{ tenant_route('tenant.restaurant-tables.index') }}" class="mt-2 inline-block text-xs font-medium text-orange-800 underline">Manage tables →</a>
                                @endif
                            @endcan
                        </span>
                    </label>
                </div>

                @php
                    $barShift = $capabilities[ModuleKeys::BAR_SHIFT] ?? [];
                    $barShiftEnabled = (bool) old('modules.bar_shift.enabled', $barShift['enabled'] ?? false);
                @endphp
                <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="modules[bar_shift][enabled]" value="0">
                        <input type="checkbox" name="modules[bar_shift][enabled]" value="1"
                               class="mt-1 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                               {{ $barShiftEnabled ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ $barShift['label'] ?? 'Bar / Shift Mode' }}</span>
                            <span class="mt-1 block text-xs text-gray-600">{{ $barShift['description'] ?? '' }}</span>
                            @if($barShift['suggested'] ?? false)
                                <span class="mt-1 inline-block rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-800">Suggested for {{ $businessTypeLabel }}</span>
                            @endif
                            <span class="mt-2 block text-xs text-gray-500">When disabled, POS uses standard direct cashier checkout without waiter shift balancing.</span>
                        </span>
                    </label>
                </div>

                @php
                    $variants = $capabilities[ModuleKeys::CATALOG_VARIANTS] ?? [];
                    $variantsEnabled = (bool) old('modules.catalog_variants.enabled', $variants['enabled'] ?? false);
                @endphp
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="modules[catalog_variants][enabled]" value="0">
                        <input type="checkbox" name="modules[catalog_variants][enabled]" value="1"
                               class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               {{ $variantsEnabled ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ $variants['label'] ?? 'Variant Catalog' }}</span>
                            <span class="mt-1 block text-xs text-gray-600">{{ $variants['description'] ?? '' }}</span>
                            @if($variants['suggested'] ?? false)
                                <span class="mt-1 inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-800">Suggested for {{ $businessTypeLabel }}</span>
                            @endif
                            <span class="mt-2 block text-xs text-gray-500">New products default to variant mode. You can still toggle variants per product on the add/edit form.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <x-button type="submit" variant="primary">Save business profile</x-button>
    </form>
</x-card>
@endsection
