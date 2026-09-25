@php
    use App\Enums\BusinessOperatingMode;
    use App\Support\BusinessModeCompliance;

    $selectedOperatingMode = old('operating_mode', $business->operatingMode());
    $serviceUnlocked = BusinessModeCompliance::isAdminUnlocked($business, BusinessModeCompliance::MODE_SERVICE);
    $rentalUnlocked = BusinessModeCompliance::isAdminUnlocked($business, BusinessModeCompliance::MODE_RENTAL);
    $serviceGlobal = BusinessModeCompliance::globallyEnabled(BusinessModeCompliance::MODE_SERVICE);
    $rentalGlobal = BusinessModeCompliance::globallyEnabled(BusinessModeCompliance::MODE_RENTAL);
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5 space-y-4">
    <div>
        <p class="text-sm font-semibold text-gray-900">How your business operates</p>
        <p class="mt-1 text-xs text-gray-500">
            Industry ({{ \App\Enums\BusinessType::label($business->business_type) }}) controls suggestions; operating mode controls specialized catalog tools. Standard retail keeps the classic POS focused on physical stock.
        </p>
    </div>

    <div>
        <label for="operating_mode" class="mb-1.5 block text-sm font-medium text-gray-700">Operating mode</label>
        <select name="operating_mode" id="operating_mode" required
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach(BusinessOperatingMode::labels() as $value => $label)
                <option value="{{ $value }}" @selected($selectedOperatingMode === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('operating_mode')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if($serviceGlobal && $serviceUnlocked)
        <label class="flex items-start gap-3 rounded-lg border border-violet-200 bg-violet-50/60 p-3 text-sm">
            <input type="hidden" name="service_based_mode_enabled" value="0">
            <input type="checkbox" name="service_based_mode_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                   @checked(old('service_based_mode_enabled', ! empty(($business->settings ?? [])['service_based_mode_enabled'])))>
            <span>
                <span class="font-semibold text-gray-900">Service-based catalog</span>
                <span class="mt-0.5 block text-xs text-gray-600">Sell non-inventory services (labour, fees, repairs) alongside products. Stock is not tracked for service items.</span>
            </span>
        </label>
    @endif

    @if($rentalGlobal && $rentalUnlocked)
        <label class="flex items-start gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 text-sm opacity-75">
            <input type="hidden" name="rental_mode_enabled" value="0">
            <input type="checkbox" name="rental_mode_enabled" value="1" disabled
                   @checked(old('rental_mode_enabled', ! empty(($business->settings ?? [])['rental_mode_enabled'])))>
            <span>
                <span class="font-semibold text-gray-900">Rentals mode</span>
                <span class="mt-0.5 block text-xs text-gray-600">Car hire, equipment, and property rentals — booking UI coming soon. Toggle is reserved for your account.</span>
            </span>
        </label>
    @endif

    @if(! $serviceUnlocked && ! $rentalUnlocked)
        <p class="text-xs text-gray-500">Specialized modes are locked for this business. Contact DukaPro support if you need service or rental catalog features.</p>
    @endif
</div>
