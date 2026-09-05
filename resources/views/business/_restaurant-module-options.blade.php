@php
    $restaurantSubDisabled = ! ($restaurantEnabled ?? false);
@endphp

<div id="restaurant-sub-options" class="space-y-4 border-t border-orange-200/80 pt-4 {{ $restaurantSubDisabled ? 'opacity-60' : '' }}">
    <label class="flex items-start gap-3">
        <input type="hidden" name="modules[restaurant][use_tables]" value="0">
        <input type="checkbox" name="modules[restaurant][use_tables]" value="1"
               data-restaurant-sub
               class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
               {{ ($tablesEnabled ?? false) ? 'checked' : '' }}
               {{ $restaurantSubDisabled ? 'disabled' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">Use restaurant tables</span>
            <span class="mt-1 block text-xs text-gray-600">Manage tables and tie orders to seating. Leave off for counter-service.</span>
            @if(! empty($showManageTablesLink) && ($restaurantEnabled ?? false))
                @can('manage-restaurant-tables')
                    <a href="{{ tenant_route('tenant.restaurant-tables.index') }}" class="mt-2 inline-block text-xs font-medium text-orange-800 underline">Manage tables →</a>
                @endcan
            @endif
        </span>
    </label>

    <label class="flex items-start gap-3">
        <input type="hidden" name="modules[restaurant][use_waiters]" value="0">
        <input type="checkbox" name="modules[restaurant][use_waiters]" value="1"
               data-restaurant-sub
               class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
               {{ ($waitersEnabled ?? false) ? 'checked' : '' }}
               {{ $restaurantSubDisabled ? 'disabled' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">Use waiters</span>
            <span class="mt-1 block text-xs text-gray-600">Floor staff take orders and cash is balanced per waiter at close. Turn off for counter-service — customers pay at the till and staff don’t need to be tracked individually.</span>
        </span>
    </label>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        function syncRestaurantSubOptions() {
            var main = document.getElementById('restaurant_mode_enabled');
            var wrap = document.getElementById('restaurant-sub-options');
            if (!main || !wrap) return;

            var enabled = main.checked;
            wrap.classList.toggle('opacity-60', !enabled);

            wrap.querySelectorAll('[data-restaurant-sub]').forEach(function (el) {
                el.disabled = !enabled;
                if (!enabled && el.type === 'checkbox') {
                    el.checked = false;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var main = document.getElementById('restaurant_mode_enabled');
            if (main) {
                main.addEventListener('change', syncRestaurantSubOptions);
                syncRestaurantSubOptions();
            }
        });
    })();
    </script>
    @endpush
@endonce
