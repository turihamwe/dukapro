@if(!empty($lowStockItems) && $lowStockItems->isNotEmpty())
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-amber-950">
                    Low stock alert
                    <span class="ml-1 rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-bold text-amber-900">{{ $lowStockItems->count() }}</span>
                </p>
                <p class="mt-1 text-xs text-amber-900/80">These items are at or below their alert threshold at your branch.</p>
            </div>
            @can('top-up-inventory')
                <a href="{{ tenant_route('tenant.inventory.top-up') }}" class="shrink-0 text-xs font-semibold text-amber-800 underline decoration-amber-300 underline-offset-2 hover:text-amber-950">Top-up stock</a>
            @endcan
        </div>
        <ul class="mt-3 space-y-1.5">
            @foreach($lowStockItems as $item)
                <li class="flex items-center justify-between gap-3 text-xs text-amber-950">
                    <span class="truncate font-medium">{{ $item['name'] }}</span>
                    <span class="shrink-0 tabular-nums text-amber-800">
                        {{ format_unit_quantity($item['available'], $item['unit'] ?? 'piece', auth()->user()->business_id) }}
                        / {{ format_unit_quantity($item['threshold'], $item['unit'] ?? 'piece', auth()->user()->business_id) }} min
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
