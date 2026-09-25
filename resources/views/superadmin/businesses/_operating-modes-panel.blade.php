@php
    use App\Support\BusinessModeCompliance;

    $settings = $business->settings ?? [];
    $serviceGlobal = BusinessModeCompliance::globallyEnabled(BusinessModeCompliance::MODE_SERVICE);
    $rentalGlobal = BusinessModeCompliance::globallyEnabled(BusinessModeCompliance::MODE_RENTAL);
    $inventoryGlobal = BusinessModeCompliance::globallyEnabled(BusinessModeCompliance::MODE_INVENTORY_ONLY);
@endphp

<div class="rounded-xl border border-violet-200 bg-violet-50/40 p-4 sm:p-5">
    <p class="text-sm font-semibold text-gray-900">Specialized operating modes</p>
    <p class="mt-1 text-xs text-gray-600">
        Level 2 unlock (like EFRIS). Turn on platform masters in System Settings first, then unlock per business. Owners toggle features on their profile.
    </p>
    <p class="mt-2 text-xs text-gray-500">Operating mode: <span class="font-medium text-gray-800">{{ \App\Enums\BusinessOperatingMode::label($business->operatingMode()) }}</span></p>

    <form method="POST" action="{{ route('superadmin.businesses.modes.unlock', $business->id) }}" class="mt-4 space-y-3">
        @csrf

        <label class="flex items-start gap-3 rounded-lg border border-white bg-white/80 p-3 text-sm {{ $serviceGlobal ? '' : 'opacity-60' }}">
            <input type="hidden" name="service_mode_admin_unlocked" value="0">
            <input type="checkbox" name="service_mode_admin_unlocked" value="1" class="mt-0.5 rounded border-gray-300 text-violet-600"
                   @disabled(! $serviceGlobal)
                   @checked(! empty($settings['service_mode_admin_unlocked']))>
            <span>
                <span class="font-medium text-gray-900">Unlock service-based catalog</span>
                @if(! $serviceGlobal)
                    <span class="block text-xs text-amber-700">Enable “Service-based businesses” in System Settings first.</span>
                @else
                    <span class="block text-xs text-gray-500">Owner can sell non-inventory services on POS when they turn it on.</span>
                @endif
            </span>
        </label>

        <label class="flex items-start gap-3 rounded-lg border border-dashed border-gray-300 bg-white/60 p-3 text-sm {{ $rentalGlobal ? '' : 'opacity-60' }}">
            <input type="hidden" name="rental_mode_admin_unlocked" value="0">
            <input type="checkbox" name="rental_mode_admin_unlocked" value="1" class="mt-0.5 rounded border-gray-300 text-gray-500"
                   @disabled(! $rentalGlobal)
                   @checked(! empty($settings['rental_mode_admin_unlocked']))>
            <span>
                <span class="font-medium text-gray-900">Unlock rentals mode (stub)</span>
                <span class="block text-xs text-gray-500">Reserved for car hire / property — UI not live yet.</span>
            </span>
        </label>

        <label class="flex items-start gap-3 rounded-lg border border-dashed border-gray-300 bg-white/60 p-3 text-sm {{ $inventoryGlobal ? '' : 'opacity-60' }}">
            <input type="hidden" name="inventory_only_mode_admin_unlocked" value="0">
            <input type="checkbox" name="inventory_only_mode_admin_unlocked" value="1" class="mt-0.5 rounded border-gray-300 text-gray-500"
                   @disabled(! $inventoryGlobal)
                   @checked(! empty($settings['inventory_only_mode_admin_unlocked']))>
            <span>
                <span class="font-medium text-gray-900">Unlock inventory-only mode (stub)</span>
                <span class="block text-xs text-gray-500">Warehouse tracking without POS — coming soon.</span>
            </span>
        </label>

        <button type="submit" class="rounded-lg bg-violet-700 px-4 py-2 text-xs font-semibold text-white hover:bg-violet-800">Save mode unlocks</button>
    </form>
</div>
