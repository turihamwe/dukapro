@php
    $floorEnabled = $floorEnabled ?? false;
    $waitersEnabled = (bool) old('floor.use_waiters', $floor['use_waiters'] ?? false);
    $tablesEnabled = (bool) old('floor.use_tables', $floor['use_tables'] ?? false);
    $floorDisabled = ! $floorEnabled;
@endphp

<div id="floor-service-options" class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4 {{ $floorDisabled ? 'opacity-60' : '' }}">
    <div>
        <p class="text-sm font-semibold text-gray-900">Floor service</p>
        <p class="mt-1 text-xs text-gray-600">How front-of-house runs for this business. Shift close (EOD) always applies — these options control table tracking and whether multiple waiters are tracked separately.</p>
    </div>

    <label class="flex items-start gap-3">
        <input type="hidden" name="floor[use_waiters]" value="0">
        <input type="checkbox" name="floor[use_waiters]" value="1"
               data-floor-sub
               class="mt-1 rounded border-gray-300 text-slate-700 focus:ring-slate-500"
               {{ $waitersEnabled ? 'checked' : '' }}
               {{ $floorDisabled ? 'disabled' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">Use waiters</span>
            <span class="mt-1 block text-xs text-gray-600">Track multiple floor staff at POS and balance each waiter’s shift before EOD. Turn off when one cashier runs the till — including you as owner in cashier mode.</span>
        </span>
    </label>

    <label class="flex items-start gap-3">
        <input type="hidden" name="floor[use_tables]" value="0">
        <input type="checkbox" name="floor[use_tables]" value="1"
               data-floor-sub
               class="mt-1 rounded border-gray-300 text-slate-700 focus:ring-slate-500"
               {{ $tablesEnabled ? 'checked' : '' }}
               {{ $floorDisabled ? 'disabled' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">Use tables</span>
            <span class="mt-1 block text-xs text-gray-600">Manage seating and tie orders to tables. Works for restaurants and bars. Leave off for counter-only service.</span>
            @if(! empty($showManageTablesLink) && ! $floorDisabled && $tablesEnabled)
                @can('manage-floor-tables')
                    <a href="{{ tenant_route('tenant.restaurant-tables.index') }}" class="mt-2 inline-block text-xs font-medium text-slate-800 underline">Manage tables →</a>
                @endcan
            @endif
        </span>
    </label>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        function syncFloorOptions() {
            var restaurant = document.getElementById('restaurant_mode_enabled');
            var bar = document.getElementById('bar_shift_mode_enabled');
            var wrap = document.getElementById('floor-service-options');
            if (!wrap) return;

            var enabled = (restaurant && restaurant.checked) || (bar && bar.checked);
            wrap.classList.toggle('opacity-60', !enabled);

            wrap.querySelectorAll('[data-floor-sub]').forEach(function (el) {
                el.disabled = !enabled;
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            ['restaurant_mode_enabled', 'bar_shift_mode_enabled'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', syncFloorOptions);
                }
            });
            syncFloorOptions();
        });
    })();
    </script>
    @endpush
@endonce
