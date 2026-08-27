@if(!$onboarding['is_complete'])
    @include('dashboard.partials.onboarding')
@endif

@php
    $m = $modernPayload;
@endphp

@if($m)
@php
    $s = $m['summary'];
    $maxBar = max(1, max(array_column($m['last_7_days'], 'value')));
@endphp
{{-- Header --}}
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Welcome back, {{ auth()->user()->name }} · {{ now()->format('l, F j, Y') }}</p>
    </div>
    @can('manage-inventory')
        <a href="{{ tenant_route('tenant.inventory.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </a>
    @endcan
</div>

{{-- Metric cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Stock Value</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_money_compact($s['inventory_value'], $business) }}</p>
        @if($m['inventory_change_pct'] > 0)
            <p class="mt-2 flex items-center gap-1 text-xs font-medium text-emerald-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                +{{ $m['inventory_change_pct'] }}% this week
            </p>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Today's Sales</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ format_money_compact($s['todays_sales'], $business) }}</p>
        <p class="mt-2 flex items-center gap-1 text-xs font-medium {{ $m['sales_change_pct'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
            @if($m['sales_change_pct'] >= 0)
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                +{{ $m['sales_change_pct'] }}% vs yesterday
            @else
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                {{ $m['sales_change_pct'] }}% vs yesterday
            @endif
        </p>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Low Stock Items</p>
        <div class="mt-2 flex items-center gap-2">
            <p class="text-2xl font-bold text-gray-900">{{ number_format($s['low_stock_count']) }}</p>
            @if($s['low_stock_count'] > 0)
                <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            @endif
        </div>
        @if($s['low_stock_count'] > 0)
            <p class="mt-2 text-xs font-medium text-red-500">Needs restock attention</p>
        @else
            <p class="mt-2 text-xs text-gray-500">All items above threshold</p>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Products</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($s['product_count']) }}</p>
        <p class="mt-2 text-xs text-gray-500">Active in inventory</p>
    </div>
</div>

{{-- Lower sections --}}
<div class="grid gap-6 xl:grid-cols-3">
    {{-- Sales overview chart --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm xl:col-span-2">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Sales Overview</h2>
                <p class="text-sm text-gray-500">Daily performance · last 7 days</p>
            </div>
        </div>
        <div class="flex h-56 items-end justify-between gap-2 sm:gap-3">
            @foreach($m['last_7_days'] as $index => $day)
                @php
                    $height = max(8, ($day['value'] / $maxBar) * 100);
                    $isToday = $index === count($m['last_7_days']) - 1;
                @endphp
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="relative flex w-full flex-1 items-end justify-center">
                        <div
                            class="w-full max-w-[48px] rounded-t-lg bg-gradient-to-t from-blue-500 to-emerald-400 shadow-sm transition-all {{ $isToday ? 'ring-2 ring-emerald-400/50' : '' }}"
                            style="height: {{ $height }}%"
                            title="{{ format_money($day['value'], $business) }}"
                        ></div>
                    </div>
                    <span class="text-[10px] font-medium text-gray-500 sm:text-xs">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Right column --}}
    <div class="space-y-6">
        {{-- Stock status --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Stock Status</h2>
            <p class="text-sm text-gray-500">Inventory health overview</p>
            <div class="mt-6 flex flex-col items-center">
                @php
                    $avail = $m['stock_status']['available_pct'];
                    $circ = 2 * M_PI * 40;
                    $availOffset = $circ * (1 - $avail / 100);
                @endphp
                <div class="relative h-36 w-36">
                    <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#fee2e2" stroke-width="10"/>
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#10B981" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $availOffset }}"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-2xl font-bold text-gray-900">{{ $avail }}%</span>
                        <span class="text-xs text-gray-500">Available</span>
                    </div>
                </div>
                <div class="mt-4 flex w-full justify-center gap-6 text-xs">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> {{ $avail }}% Available</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-300"></span> {{ $m['stock_status']['missing_pct'] }}% Low</span>
                </div>
            </div>
        </div>

        {{-- Recent sales --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Sales</h2>
                @can('view-sales-reports')
                    <a href="{{ tenant_route('tenant.reports.sales.index') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">View all →</a>
                @endcan
            </div>
            @if($m['recent_sales']->isEmpty())
                <p class="py-6 text-center text-sm text-gray-500">No sales recorded yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 text-gray-500">
                                <th class="pb-2 pr-2 font-medium">Product</th>
                                <th class="pb-2 pr-2 font-medium">Qty</th>
                                <th class="pb-2 pr-2 font-medium">Price</th>
                                <th class="pb-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($m['recent_sales'] as $sale)
                                @php
                                    $firstItem = $sale->items->first();
                                    $productName = 'Sale #' . $sale->sale_number;
                                    if ($firstItem) {
                                        if ($firstItem->product) {
                                            $productName = $firstItem->product->name;
                                        } elseif ($firstItem->product_name) {
                                            $productName = $firstItem->product_name;
                                        }
                                    }
                                    $qty = $sale->items->sum('quantity');
                                    $isCompleted = $sale->status === 'completed';
                                @endphp
                                <tr>
                                    <td class="py-2.5 pr-2">
                                        <p class="font-medium text-gray-900">{{ \Illuminate\Support\Str::limit($productName, 18) }}</p>
                                        <p class="text-[10px] text-gray-400">{{ optional($sale->completed_at)->format('M d') }}</p>
                                    </td>
                                    <td class="py-2.5 pr-2 text-gray-600">{{ $qty }}</td>
                                    <td class="py-2.5 pr-2 font-medium text-gray-900">{{ format_money_compact($sale->total, $business) }}</td>
                                    <td class="py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $isCompleted ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $isCompleted ? 'Completed' : ucfirst($sale->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
