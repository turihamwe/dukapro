@php
    $dropdownLimit = 8;
    $remaining = max(0, $lowStockCount - $lowStockDropdownItems->count());
@endphp
<div class="relative" id="low-stock-notifications">
    <button type="button"
            id="low-stock-notifications-toggle"
            class="relative inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"
            aria-label="Low stock notifications"
            aria-expanded="false"
            aria-controls="low-stock-notifications-panel">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($lowStockCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $lowStockCount > 9 ? '9+' : $lowStockCount }}
            </span>
        @endif
    </button>

    <div id="low-stock-notifications-panel"
         class="absolute right-0 z-50 mt-2 hidden w-[min(100vw-2rem,20rem)] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
         role="menu">
        <div class="border-b border-gray-100 px-4 py-3">
            <p class="text-sm font-semibold text-gray-900">Low stock</p>
            <p class="text-xs text-gray-500">Products at or below critical level</p>
        </div>

        @if($lowStockDropdownItems->isEmpty())
            <p class="px-4 py-6 text-center text-sm text-gray-500">All products are above your stock thresholds.</p>
        @else
            <ul class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                @foreach($lowStockDropdownItems as $item)
                    <li>
                        <a href="{{ tenant_route('tenant.inventory.show', ['product' => $item['id']]) }}"
                           class="block px-4 py-3 transition hover:bg-gray-50"
                           role="menuitem">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $item['name'] }}</p>
                            <p class="mt-0.5 text-xs text-rose-600">
                                {{ number_format($item['available'], 2) }} {{ $item['unit'] ?? 'units' }}
                                <span class="text-gray-400">· threshold {{ number_format($item['threshold'], 2) }}</span>
                            </p>
                            @if(!empty($item['branch_name']))
                                <p class="mt-0.5 truncate text-[11px] text-gray-400">{{ $item['branch_name'] }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="border-t border-gray-100 bg-gray-50 px-4 py-2.5">
            <a href="{{ tenant_route('tenant.inventory.index', ['stock' => 'low']) }}"
               class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                @if($lowStockCount > 0)
                    View all {{ number_format($lowStockCount) }} alert{{ $lowStockCount === 1 ? '' : 's' }}
                    @if($remaining > 0)
                        ({{ $remaining }} more not shown)
                    @endif
                    →
                @else
                    Open stock alerts →
                @endif
            </a>
        </div>
    </div>
</div>
