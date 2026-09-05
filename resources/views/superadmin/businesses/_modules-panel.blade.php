@php
    use App\Enums\BusinessType;
    use App\Modules\ModuleKeys;
    use App\Support\BillingMode;

    $businessTypeLabel = BusinessType::label($business->business_type);
    $moduleRecords = $business->businessModules->keyBy('module_key');
    $sourceLabels = [
        \App\Services\BusinessModuleService::SOURCE_OWNER => 'Owner',
        \App\Services\BusinessModuleService::SOURCE_SUPERADMIN => 'Superadmin',
        \App\Services\BusinessModuleService::SOURCE_MIGRATION => 'Migration',
        \App\Services\BusinessModuleService::SOURCE_PRESET => 'Signup preset',
    ];
    $moduleRegistry = app(\App\Modules\ModuleRegistry::class);
    $moduleFootnotes = [
        ModuleKeys::BAR_SHIFT => 'Turn on for a bar or pub with no kitchen. Configure waiters and tables in Floor service below.',
    ];
    $restaurantEnabled = (bool) old('modules.restaurant.enabled', ($capabilities[ModuleKeys::RESTAURANT]['enabled'] ?? false));
    $barEnabled = (bool) old('modules.bar_shift.enabled', ($capabilities[ModuleKeys::BAR_SHIFT]['enabled'] ?? false));
    $floorEnabled = $restaurantEnabled || $barEnabled;
    $moduleStyles = [
        ModuleKeys::BAR_SHIFT => [
            'border' => 'border-violet-200',
            'bg' => 'bg-white',
            'checkbox' => 'text-violet-600 focus:ring-violet-500',
            'badge' => 'bg-violet-100 text-violet-800',
        ],
        ModuleKeys::CATALOG_VARIANTS => [
            'border' => 'border-indigo-200',
            'bg' => 'bg-white',
            'checkbox' => 'text-indigo-600 focus:ring-indigo-500',
            'badge' => 'bg-indigo-100 text-indigo-800',
        ],
    ];
@endphp

<div class="rounded-xl border border-violet-200 bg-violet-50/40 p-6">
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Modules</h2>
            <p class="mt-1 text-sm text-gray-600">
                Enable or disable capabilities for <strong>{{ $business->name }}</strong>.
                @if(BillingMode::isAddons())
                    In add-on billing mode, module access also depends on subscription, comping, or grandfathering below.
                @else
                    Changes apply immediately — unified billing (no per-module fees).
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('superadmin.impersonate.start', $business->id) }}" class="shrink-0">
            @csrf
            <button type="submit" class="rounded-lg border border-violet-300 bg-white px-4 py-2 text-sm font-medium text-violet-800 hover:bg-violet-50">
                View as owner →
            </button>
        </form>
    </div>

    @can('platform-full-access')
        <form method="POST" action="{{ route('superadmin.businesses.modules.update', ['businessId' => $business->id]) }}" class="space-y-5">
            @csrf

            @if(BillingMode::isAddons())
                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <input type="hidden" name="billing_grandfathered" value="0">
                    <input type="checkbox" name="billing_grandfathered" value="1" id="billing_grandfathered"
                           class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                           {{ old('billing_grandfathered', $business->billing_grandfathered) ? 'checked' : '' }}>
                    <div>
                        <label for="billing_grandfathered" class="block text-sm font-medium text-gray-900">Grandfathered flat pricing</label>
                        <p class="mt-0.5 text-xs text-gray-600">When checked, this business pays only the base subscription — all modules are free. Use for existing customers when switching to add-on billing.</p>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                @foreach($moduleRegistry->all() as $moduleKey => $definition)
                    @if($moduleKey === ModuleKeys::RESTAURANT)
                        @php
                            $restaurant = $capabilities[$moduleKey] ?? [];
                            $restaurantRecord = $moduleRecords->get(ModuleKeys::RESTAURANT);
                        @endphp
                        <div class="rounded-xl border border-orange-200 bg-white p-4 space-y-3">
                            <label class="flex items-start gap-3">
                                <input type="hidden" name="modules[restaurant][enabled]" value="0">
                                <input type="checkbox" name="modules[restaurant][enabled]" value="1" id="restaurant_mode_enabled"
                                       class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                       {{ $restaurantEnabled ? 'checked' : '' }}>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900">{{ $restaurant['label'] ?? 'Restaurant Mode' }}</span>
                                        @if($restaurantRecord && $restaurantRecord->source)
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">
                                                {{ $sourceLabels[$restaurantRecord->source] ?? $restaurantRecord->source }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="mt-1 block text-xs text-gray-600">{{ $restaurant['description'] ?? '' }}</span>
                                    @if($restaurant['suggested'] ?? false)
                                        <span class="mt-1 inline-block rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-orange-800">Suggested for {{ $businessTypeLabel }}</span>
                                    @endif
                                </span>
                            </label>
                            @if(BillingMode::isAddons() && ! old('billing_grandfathered', $business->billing_grandfathered))
                                @php $restaurantBilling = $capabilities[ModuleKeys::RESTAURANT]['billing'] ?? []; @endphp
                                @if($restaurantBilling['billable'] ?? false)
                                    <label class="flex items-center gap-2 text-xs text-gray-600">
                                        <input type="hidden" name="modules[restaurant][billing_comped]" value="0">
                                        <input type="checkbox" name="modules[restaurant][billing_comped]" value="1"
                                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                               {{ old('modules.restaurant.billing_comped', optional($restaurantRecord)->billing_comped ?? false) ? 'checked' : '' }}>
                                        Comp this add-on (free for tenant)
                                    </label>
                                @endif
                            @endif
                        </div>
                    @else
                        @php
                            $record = $moduleRecords->get($moduleKey);
                            $sourceLabel = ($record && $record->source)
                                ? ($sourceLabels[$record->source] ?? $record->source)
                                : null;
                        @endphp
                        @include('business._capability-module', [
                            'moduleKey' => $moduleKey,
                            'inputId' => $moduleKey === ModuleKeys::BAR_SHIFT ? 'bar_shift_mode_enabled' : null,
                            'capability' => $capabilities[$moduleKey] ?? [
                                'label' => $definition->label(),
                                'description' => $definition->description(),
                                'enabled' => false,
                                'suggested' => $definition->defaultEnabledFor($business),
                            ],
                            'businessTypeLabel' => $businessTypeLabel,
                            'styles' => $moduleStyles[$moduleKey] ?? [
                                'border' => 'border-gray-200',
                                'bg' => 'bg-white',
                                'checkbox' => 'text-gray-700 focus:ring-gray-500',
                                'badge' => 'bg-gray-100 text-gray-800',
                            ],
                            'footnote' => $moduleFootnotes[$moduleKey] ?? null,
                            'sourceLabel' => $sourceLabel,
                        ])
                        @if(BillingMode::isAddons() && ! old('billing_grandfathered', $business->billing_grandfathered))
                            @php
                                $moduleBilling = $capabilities[$moduleKey]['billing'] ?? [];
                                $moduleRecord = $record;
                            @endphp
                            @if($moduleBilling['billable'] ?? false)
                                <label class="-mt-2 ml-7 flex items-center gap-2 text-xs text-gray-600">
                                    <input type="hidden" name="modules[{{ $moduleKey }}][billing_comped]" value="0">
                                    <input type="checkbox" name="modules[{{ $moduleKey }}][billing_comped]" value="1"
                                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                           {{ old("modules.{$moduleKey}.billing_comped", optional($moduleRecord)->billing_comped ?? false) ? 'checked' : '' }}>
                                    Comp this add-on (free for tenant)
                                </label>
                            @endif
                        @endif
                    @endif
                @endforeach

                @include('business._floor-service-options', [
                    'floor' => $floor ?? $business->floorSettings(),
                    'floorEnabled' => $floorEnabled,
                    'showManageTablesLink' => false,
                ])
            </div>

            <div>
                <label for="module_note" class="mb-1 block text-sm font-medium text-gray-700">Audit note <span class="font-normal text-gray-400">(optional)</span></label>
                <textarea id="module_note" name="note" rows="2" maxlength="500" placeholder="Why are you changing modules? Recorded in the platform activity log."
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('note') }}</textarea>
            </div>

            <button type="submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                Save modules
            </button>
        </form>
    @else
        <div class="space-y-3">
            @foreach($capabilities as $key => $capability)
                @php $record = $moduleRecords->get($key); @endphp
                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm">
                    <div>
                        <p class="font-medium text-gray-900">{{ $capability['label'] ?? $key }}</p>
                        @if($record && $record->source)
                            <p class="text-xs text-gray-500">Last set by {{ $sourceLabels[$record->source] ?? $record->source }}</p>
                        @endif
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ($capability['enabled'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ ($capability['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-gray-500">Full superadmin access is required to change modules.</p>
    @endcan
</div>
