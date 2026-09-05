@extends('layouts.admin')

@section('title', 'Business Profile')

@section('content')
@php
    use App\Enums\BusinessType;
    use App\Modules\ModuleKeys;

    $businessTypeLabel = BusinessType::label($business->business_type);
    $moduleRegistry = app(\App\Modules\ModuleRegistry::class);
    $moduleFootnotes = [
        ModuleKeys::BAR_SHIFT => 'Turn on for a bar or pub with no kitchen. Waiters are included automatically — you don\'t need Restaurant Mode.',
        ModuleKeys::CATALOG_VARIANTS => 'New products can use size/color variants. You can still toggle variants per product on the add/edit form.',
    ];
    $moduleStyles = [
        ModuleKeys::BAR_SHIFT => [
            'border' => 'border-violet-200',
            'bg' => 'bg-violet-50',
            'checkbox' => 'text-violet-600 focus:ring-violet-500',
            'badge' => 'bg-violet-100 text-violet-800',
        ],
        ModuleKeys::CATALOG_VARIANTS => [
            'border' => 'border-indigo-200',
            'bg' => 'bg-indigo-50',
            'checkbox' => 'text-indigo-600 focus:ring-indigo-500',
            'badge' => 'bg-indigo-100 text-indigo-800',
        ],
    ];
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
                @foreach($moduleRegistry->all() as $moduleKey => $definition)
                    @if($moduleKey === ModuleKeys::RESTAURANT)
                        @php
                            $restaurant = $capabilities[$moduleKey] ?? [];
                            $restaurantEnabled = (bool) old('modules.restaurant.enabled', $restaurant['enabled'] ?? false);
                            $tablesEnabled = (bool) old('modules.restaurant.use_tables', $restaurant['settings']['use_tables'] ?? false);
                            $waitersEnabled = (bool) old('modules.restaurant.use_waiters', $restaurant['settings']['use_waiters'] ?? false);
                        @endphp
                        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 space-y-4">
                            <label class="flex items-start gap-3">
                                <input type="hidden" name="modules[restaurant][enabled]" value="0">
                                <input type="checkbox" name="modules[restaurant][enabled]" value="1" id="restaurant_mode_enabled"
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
                            @include('business._restaurant-module-options', [
                                'restaurantEnabled' => $restaurantEnabled,
                                'tablesEnabled' => $tablesEnabled,
                                'waitersEnabled' => $waitersEnabled,
                                'showManageTablesLink' => true,
                            ])
                        </div>
                    @else
                        @include('business._capability-module', [
                            'moduleKey' => $moduleKey,
                            'capability' => $capabilities[$moduleKey] ?? [
                                'label' => $definition->label(),
                                'description' => $definition->description(),
                                'enabled' => false,
                                'suggested' => $definition->defaultEnabledFor($business),
                            ],
                            'businessTypeLabel' => $businessTypeLabel,
                            'styles' => $moduleStyles[$moduleKey] ?? [
                                'border' => 'border-gray-200',
                                'bg' => 'bg-gray-50',
                                'checkbox' => 'text-gray-700 focus:ring-gray-500',
                                'badge' => 'bg-gray-100 text-gray-800',
                            ],
                            'footnote' => $moduleFootnotes[$moduleKey] ?? null,
                        ])
                    @endif
                @endforeach
            </div>
        </div>

        <x-button type="submit" variant="primary">Save business profile</x-button>
    </form>
</x-card>
@endsection
