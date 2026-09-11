<div class="grid gap-4 grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
    <x-stat-card label="Total Sales" :value="format_money($totals['sales_total'])" accent="emerald" />
    <x-stat-card label="Transactions" :value="number_format($totals['sales_count'])" accent="indigo" />
    <x-stat-card label="Cash" :value="format_money($totals['cash'])" accent="indigo" />
    <x-stat-card label="Mobile Money" :value="format_money($totals['mobile_money'])" accent="amber" />
    <x-stat-card label="Bank" :value="format_money($totals['bank'])" accent="sky" />
</div>

<x-card class="mt-6" :padding="false">
    <div class="border-b border-gray-100 px-4 py-3 sm:px-6">
        <h2 class="text-sm font-semibold text-gray-900">Products sold</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                    <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qty sold</th>
                    <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Revenue</th>
                    <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Sales</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($productSummary as $row)
                    <tr>
                        <td class="px-4 sm:px-6 py-3 text-sm text-gray-900">{{ $row->product_name }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right text-sm text-gray-600">{{ number_format($row->total_quantity, 2) }}</td>
                        <td class="px-4 sm:px-6 py-3 text-right text-sm font-medium text-gray-900">@money($row->total_revenue)</td>
                        <td class="px-4 sm:px-6 py-3 text-right text-sm text-gray-600">{{ number_format($row->sale_count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No product sales on this day.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<x-card class="mt-6" :padding="false">
    <div class="border-b border-gray-100 px-4 py-3 sm:px-6">
        <h2 class="text-sm font-semibold text-gray-900">Sales by transaction</h2>
        <p class="text-xs text-gray-500">Cashier and line items for each sale.</p>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($sales as $sale)
            <div class="px-4 py-4 sm:px-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $sale->sale_number }}</p>
                        <p class="text-xs text-gray-500">
                            {{ optional($sale->completed_at)->format('g:i A') ?? '—' }}
                            · {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-emerald-700">@money($sale->total)</p>
                        <p class="text-xs text-gray-600">Cashier: {{ optional($sale->user)->name ?? '—' }}</p>
                    </div>
                </div>
                @if($sale->items->isNotEmpty())
                    <ul class="mt-3 space-y-1 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-700">
                        @foreach($sale->items as $item)
                            <li class="flex justify-between gap-3">
                                <span>{{ $item->product_name }} × {{ format_unit_quantity($item->quantity, 'piece', auth()->user()->business_id) }}</span>
                                <span class="font-medium">@money($item->subtotal)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="px-6 py-10 text-center text-sm text-gray-500">No sales recorded for this day.</p>
        @endforelse
    </div>
</x-card>
