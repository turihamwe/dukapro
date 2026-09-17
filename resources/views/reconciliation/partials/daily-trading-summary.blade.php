@php
    use App\Support\SaleDocument;

    $canViewProfit = auth()->user()->can('view-profit-margins');
    $selectedDate = $tradingReport['date'];
    $showDatePicker = $showDatePicker ?? auth()->user()->can('view-all-reconciliations');
    $datePickerAction = $datePickerAction ?? tenant_route('tenant.reconciliation.daily');
    $saleCount = (int) $tradingReport['sale_count'];
@endphp

<div class="mb-6 space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Daily trading summary</h2>
            <p class="mt-1 text-xs text-gray-500">Completed sales performance for {{ $selectedDate->format('l, M j, Y') }}.</p>
        </div>
        @if($showDatePicker)
            <form method="GET" action="{{ $datePickerAction }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="trading_date" class="mb-1 block text-xs font-medium text-gray-600">Trading date</label>
                    <input type="date" name="date" id="trading_date" value="{{ $selectedDate->toDateString() }}"
                           max="{{ now()->toDateString() }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                </div>
                <x-button variant="secondary" size="sm" type="submit">Go</x-button>
            </form>
        @endif
    </div>

    <div class="rounded-xl border border-indigo-200 bg-indigo-50/70 p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800/70">Executive summary</p>
        <p class="mt-3 text-base leading-relaxed text-indigo-950">{{ $tradingReport['executive_summary'] }}</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 {{ $canViewProfit ? 'lg:grid-cols-3' : '' }}">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Total revenue</p>
            <p class="mt-2 text-2xl font-bold text-emerald-950">@money($tradingReport['total_revenue'])</p>
            <p class="mt-1 text-xs text-emerald-900/70">{{ number_format($saleCount) }} completed {{ $saleCount === 1 ? 'sale' : 'sales' }}</p>
        </div>
        @if($canViewProfit)
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-800">Gross profit</p>
                <p class="mt-2 text-2xl font-bold text-violet-950">@money($tradingReport['gross_profit'])</p>
                <p class="mt-1 text-xs text-violet-900/70">Revenue minus recorded item costs</p>
            </div>
        @endif
        @if($tradingReport['top_items']->isNotEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-5 {{ $canViewProfit ? '' : 'sm:col-span-1' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Top sellers</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700">
                    @foreach($tradingReport['top_items']->take(3) as $item)
                        <li class="flex items-start justify-between gap-3">
                            <span class="font-medium text-gray-900">{{ $item->product_name }}</span>
                            <span class="shrink-0 text-gray-600">
                                {{ rtrim(rtrim(number_format((float) $item->total_quantity, 2, '.', ''), '0'), '.') }}
                                {{ $item->measurement_unit ?: 'units' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Sales audit trail</h3>
            <p class="mt-1 text-xs text-gray-500">All receipts and invoices completed on {{ $selectedDate->format('M j, Y') }}.</p>
        </div>

        {{-- Mobile cards --}}
        <div class="divide-y divide-gray-100 md:hidden">
            @forelse($tradingReport['sales'] as $sale)
                @php
                    $itemsSummary = $sale->items->take(3)->map(function ($item) {
                        $qty = floor($item->quantity) == $item->quantity
                            ? (int) $item->quantity
                            : rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.');
                        $unit = $item->measurement_unit ? ' ' . $item->measurement_unit : '';

                        return $qty . $unit . ' × ' . $item->product_name;
                    })->implode(', ');
                    if ($sale->items->count() > 3) {
                        $itemsSummary .= ' +' . ($sale->items->count() - 3) . ' more';
                    }
                @endphp
                <div class="space-y-2 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-gray-900">{{ $sale->sale_number }}</p>
                            <p class="text-xs text-gray-500">{{ optional($sale->completed_at)->format('g:i A') ?? '—' }}</p>
                        </div>
                        <p class="font-semibold text-gray-900">@money($sale->total)</p>
                    </div>
                    <p class="text-xs text-gray-600">{{ $itemsSummary ?: 'No line items' }}</p>
                    <div class="flex items-center justify-between gap-3">
                        @if($sale->is_credit_sale)
                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Credit</span>
                        @else
                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Cash</span>
                        @endif
                        <a href="{{ SaleDocument::url($sale) }}" target="_blank" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                            View {{ SaleDocument::title($sale) }} →
                        </a>
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-gray-500">No sales recorded for this date.</p>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Receipt / invoice</th>
                        <th class="px-5 py-3">Time</th>
                        <th class="px-5 py-3">Customer type</th>
                        <th class="px-5 py-3">Items sold</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Document</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tradingReport['sales'] as $sale)
                        @php
                            $itemsSummary = $sale->items->take(3)->map(function ($item) {
                                $qty = floor($item->quantity) == $item->quantity
                                    ? (int) $item->quantity
                                    : rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.');
                                $unit = $item->measurement_unit ? ' ' . $item->measurement_unit : '';

                                return $qty . $unit . ' × ' . $item->product_name;
                            })->implode(', ');
                            if ($sale->items->count() > 3) {
                                $itemsSummary .= ' +' . ($sale->items->count() - 3) . ' more';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $sale->sale_number }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ optional($sale->completed_at)->format('g:i A') ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @if($sale->is_credit_sale)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Credit</span>
                                    @if($sale->customer)
                                        <p class="mt-1 text-xs text-gray-500">{{ $sale->customer->name }}</p>
                                    @endif
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Cash</span>
                                @endif
                            </td>
                            <td class="max-w-xs px-5 py-3 text-gray-600">{{ $itemsSummary ?: '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">@money($sale->total)</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ SaleDocument::url($sale) }}" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ SaleDocument::title($sale) }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">No sales recorded for this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
