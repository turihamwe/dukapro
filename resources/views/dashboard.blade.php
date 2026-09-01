@extends('layouts.admin')

@section('title', 'Owner Dashboard')

@section('content')
@php
    $canViewMargins = auth()->user()->can('view-profit-margins');
@endphp

@if(user_ui_theme() === 'modern')
    @include('dashboard.modern')
@else

@if(!$onboarding['is_complete'])
    @include('dashboard.partials.onboarding')
@endif

{{-- Owner summary cards --}}
@if($summaryCards)
<div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-stat-card label="Total stock value" :value="format_money($summaryCards['inventory_value'])" accent="indigo" />
    <x-stat-card label="Total sales value" :value="format_money($summaryCards['retail_stock_value'] ?? 0)" accent="emerald" />
    <x-stat-card label="Potential profit" :value="format_money($summaryCards['potential_profit'] ?? 0)" accent="emerald" />
    <x-stat-card label="Today's sales" :value="format_money($summaryCards['todays_sales'])" accent="emerald" />
    <x-stat-card label="Low stock items" :value="number_format($summaryCards['low_stock_count'])" accent="amber" />
    <x-stat-card label="Total products" :value="number_format($summaryCards['product_count'])" accent="indigo" />
</div>
@endif

@if($showFullDashboard && $stats)
@php
    $activeRangeKey = $range->key;
    $customFrom = request('from', $range->key === 'custom' ? $range->start->toDateString() : '');
    $customTo = request('to', $range->key === 'custom' ? $range->end->toDateString() : '');
    $executive = $stats['executive'];
@endphp

<x-page-header
    title="Dashboard"
    subtitle="{{ $business->name }} · {{ $range->label }}"
/>

{{-- Legacy period summary removed — see owner summary cards above --}}

{{-- Time-range filter bar --}}
<div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Time Range</p>
            <p id="range-subtitle" class="mt-0.5 text-sm text-gray-700">{{ $range->label }}</p>
        </div>
        <div class="flex flex-wrap gap-2" id="range-pills" role="group" aria-label="Analytics time range">
            @foreach($rangePresets as $preset)
                <button
                    type="button"
                    data-range="{{ $preset['key'] }}"
                    class="range-pill rounded-full border px-3 py-1.5 text-xs font-medium transition sm:px-4 sm:text-sm {{ $activeRangeKey === $preset['key'] ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm' : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300 hover:bg-white' }}"
                >
                    {{ $preset['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div id="custom-range-panel" class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-end {{ $activeRangeKey === 'custom' ? '' : 'hidden' }}">
        <div class="flex-1">
            <label for="custom-from" class="mb-1 block text-xs font-medium text-gray-600">From</label>
            <input type="date" id="custom-from" value="{{ $customFrom }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        </div>
        <div class="flex-1">
            <label for="custom-to" class="mb-1 block text-xs font-medium text-gray-600">To</label>
            <input type="date" id="custom-to" value="{{ $customTo }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
        </div>
        <button type="button" id="apply-custom-range" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
            Apply Period
        </button>
    </div>
</div>

{{-- Executive charts --}}
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    {{-- Revenue donut --}}
    <x-card class="relative overflow-hidden">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Sales Performance</h2>
                <p class="mt-0.5 text-sm text-gray-500">Revenue by source for selected period</p>
            </div>
            <span id="revenue-loading" class="hidden text-xs text-indigo-600">Updating…</span>
        </div>
        <div class="flex flex-col items-center gap-6 lg:flex-row lg:items-start">
            <div class="relative mx-auto h-64 w-64 shrink-0 sm:h-72 sm:w-72">
                <canvas id="revenue-chart" aria-label="Revenue by source chart"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Revenue</p>
                    <p id="revenue-total" class="mt-1 text-lg font-bold text-gray-900 sm:text-xl">{{ format_money($revenueChart['total']) }}</p>
                </div>
            </div>
            <div id="revenue-legend" class="w-full flex-1 space-y-2 lg:pt-4"></div>
        </div>
    </x-card>

    {{-- Stock bar chart --}}
    <x-card>
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Inventory &amp; Stock Alerts</h2>
                <p class="mt-0.5 text-sm text-gray-500">Fast-moving stock vs. critical low threshold</p>
            </div>
            <span id="stock-loading" class="hidden text-xs text-indigo-600">Updating…</span>
        </div>
        <div class="mb-3 flex flex-wrap gap-3 text-xs text-gray-600">
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> Safe stock</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-orange-500"></span> Low</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-600"></span> Critical</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-4 w-6 border-t-2 border-dashed border-red-600"></span> Critical Low Threshold</span>
        </div>
        <div class="relative h-80 w-full">
            <canvas id="stock-chart" aria-label="Inventory stock levels chart"></canvas>
        </div>
    </x-card>
</div>

{{-- Secondary panels --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <x-card>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Low Stock Alerts</h2>
            <x-badge color="red">{{ $stats['low_stock']->count() }} items</x-badge>
        </div>
        @if($stats['low_stock']->isNotEmpty())
            <ul class="divide-y divide-gray-100">
                @foreach($stats['low_stock'] as $product)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $product->sku ?? 'No SKU' }}</p>
                        </div>
                        <span class="font-semibold text-red-600">{{ $product->stock_quantity }} {{ $product->measurement_unit }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500">All products are above the low-stock threshold.</p>
        @endif
        @can('manage-inventory')
            <a href="{{ tenant_route('tenant.inventory.index') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700">Manage inventory →</a>
        @endcan
    </x-card>

    <x-card>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Cashier EOD Reports</h2>
            <a href="{{ tenant_route('tenant.reconciliation.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View all</a>
        </div>
        @if($stats['eod_reports']->isNotEmpty())
            <ul class="divide-y divide-gray-100">
                @foreach($stats['eod_reports'] as $report)
                    <li class="flex items-start justify-between gap-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $report->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $report->reconciliation_date->format('M d, Y') }}</p>
                        </div>
                        <div class="text-right text-xs">
                            <p>Missing <span class="{{ ($report->missing_money ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">@money($report->missing_money ?? 0)</span></p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500">No cashier shift reports submitted yet.</p>
        @endif
    </x-card>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @can('manage-inventory')
        <x-button variant="secondary" href="{{ tenant_route('tenant.inventory.index') }}">Inventory ({{ $stats['product_count'] }} products)</x-button>
    @endcan
    @can('manage-debts')
        <x-button variant="secondary" href="{{ tenant_route('tenant.contacts.index') }}">Contacts</x-button>
    @endcan
    @can('log-damages')
        <x-button variant="secondary" href="{{ tenant_route('tenant.damages.index') }}">Damages & Write-offs</x-button>
    @endcan
</div>

@if($business->subscription_status !== 'active' && auth()->user()->can('manage-billing'))
    <x-alert type="info" class="mt-6">
        Subscription: <strong>{{ ucfirst($business->subscription_status) }}</strong>
        @if($business->trial_ends_at)
            · Trial ends {{ $business->trial_ends_at->format('M d, Y') }}
        @endif
    </x-alert>
@endif
@endif

@endif
@endsection

@if(user_ui_theme() !== 'modern' && $showFullDashboard && $stats)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>
<script>
(function () {
    const analyticsUrl = @json(tenant_route('tenant.dashboard.analytics'));
    const dashboardUrl = @json(tenant_route('tenant.dashboard'));
    const initialRevenue = @json($revenueChart);
    const initialStock = @json($stockChart);
    const initialRange = @json($range->toArray());
    const canViewMargins = @json($canViewMargins);

    let activeRange = initialRange.key;
    let revenueChart = null;
    let stockChart = null;

    const els = {
        pills: document.querySelectorAll('.range-pill'),
        customPanel: document.getElementById('custom-range-panel'),
        customFrom: document.getElementById('custom-from'),
        customTo: document.getElementById('custom-to'),
        applyCustom: document.getElementById('apply-custom-range'),
        rangeSubtitle: document.getElementById('range-subtitle'),
        revenueTotal: document.getElementById('revenue-total'),
        revenueLegend: document.getElementById('revenue-legend'),
        revenueLoading: document.getElementById('revenue-loading'),
        stockLoading: document.getElementById('stock-loading'),
        statPeriodSales: document.querySelector('#stat-period-sales p:last-child'),
        statGrossProfit: document.querySelector('#stat-gross-profit p:last-child'),
        statGrossMargin: document.querySelector('#stat-gross-margin p:last-child'),
        statInventoryValue: document.querySelector('#stat-inventory-value p:last-child'),
        statSaleCount: document.getElementById('stat-sale-count'),
        statLowStockCount: document.getElementById('stat-low-stock-count'),
    };

    function setPillActive(key) {
        els.pills.forEach(function (pill) {
            const isActive = pill.dataset.range === key;
            pill.classList.toggle('border-indigo-600', isActive);
            pill.classList.toggle('bg-indigo-600', isActive);
            pill.classList.toggle('text-white', isActive);
            pill.classList.toggle('shadow-sm', isActive);
            pill.classList.toggle('border-gray-200', !isActive);
            pill.classList.toggle('bg-gray-50', !isActive);
            pill.classList.toggle('text-gray-700', !isActive);
        });
        els.customPanel.classList.toggle('hidden', key !== 'custom');
    }

    function buildQueryParams(rangeKey) {
        const params = new URLSearchParams({ range: rangeKey });
        if (rangeKey === 'custom') {
            params.set('from', els.customFrom.value);
            params.set('to', els.customTo.value);
        }
        return params;
    }

    function updateLegend(revenue) {
        els.revenueLegend.innerHTML = '';
        const total = revenue.total || 0;
        revenue.labels.forEach(function (label, i) {
            const value = revenue.values[i] || 0;
            const color = revenue.colors[i] || '#94a3b8';
            const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm';
            row.innerHTML =
                '<div class="flex min-w-0 items-center gap-2">' +
                    '<span class="h-3 w-3 shrink-0 rounded-full" style="background:' + color + '"></span>' +
                    '<span class="truncate font-medium text-gray-800">' + label + '</span>' +
                '</div>' +
                '<div class="shrink-0 text-right">' +
                    '<span class="font-semibold text-gray-900">' + formatNumber(value) + '</span>' +
                    '<span class="ml-2 text-xs text-gray-500">' + pct + '%</span>' +
                '</div>';
            els.revenueLegend.appendChild(row);
        });
    }

    function formatNumber(amount) {
        return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(amount);
    }

    function renderRevenueChart(data) {
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        if (revenueChart) {
            revenueChart.destroy();
        }
        revenueChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.colors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed || 0;
                                const total = data.total || 0;
                                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + formatNumber(value) + ' (' + pct + '%)';
                            },
                        },
                    },
                },
            },
        });
        els.revenueTotal.textContent = data.total_formatted || formatNumber(data.total);
        updateLegend(data);
    }

    function renderStockChart(data) {
        const ctx = document.getElementById('stock-chart').getContext('2d');
        if (stockChart) {
            stockChart.destroy();
        }
        const threshold = data.threshold || 5;
        stockChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Current Stock',
                    data: data.stock,
                    backgroundColor: data.colors,
                    borderRadius: 4,
                    maxBarThickness: 36,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: { size: 10 },
                        },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Units in Stock' },
                        grid: { color: '#f1f5f9' },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (context) {
                                const sold = (data.sold_in_period || [])[context.dataIndex];
                                return sold != null ? 'Sold in period: ' + sold + ' units' : '';
                            },
                        },
                    },
                    annotation: {
                        annotations: {
                            thresholdLine: {
                                type: 'line',
                                yMin: threshold,
                                yMax: threshold,
                                borderColor: '#dc2626',
                                borderWidth: 2,
                                borderDash: [6, 4],
                                label: {
                                    display: true,
                                    content: 'Critical Low Threshold (' + threshold + ')',
                                    position: 'end',
                                    backgroundColor: 'rgba(220, 38, 38, 0.9)',
                                    color: '#fff',
                                    font: { size: 10 },
                                    padding: 4,
                                },
                            },
                        },
                    },
                },
            },
        });
    }

    function updateStats(stats) {
        if (els.statPeriodSales) {
            els.statPeriodSales.textContent = stats.period_sales_formatted;
        }
        if (canViewMargins && els.statGrossProfit) {
            els.statGrossProfit.textContent = stats.gross_profit_formatted;
        }
        if (canViewMargins && els.statGrossMargin) {
            els.statGrossMargin.textContent = stats.gross_margin + '%';
        }
        if (els.statInventoryValue) {
            els.statInventoryValue.textContent = stats.inventory_value_formatted;
        }
        if (els.statSaleCount) {
            els.statSaleCount.textContent = new Intl.NumberFormat().format(stats.sale_count);
        }
        if (els.statLowStockCount) {
            els.statLowStockCount.textContent = new Intl.NumberFormat().format(stats.low_stock_count);
        }
    }

    function setLoading(isLoading) {
        els.revenueLoading.classList.toggle('hidden', !isLoading);
        els.stockLoading.classList.toggle('hidden', !isLoading);
        els.pills.forEach(function (pill) {
            pill.disabled = isLoading;
            pill.classList.toggle('opacity-60', isLoading);
        });
    }

    async function fetchAnalytics(rangeKey) {
        if (rangeKey === 'custom' && (!els.customFrom.value || !els.customTo.value)) {
            return;
        }

        setLoading(true);
        const params = buildQueryParams(rangeKey);

        try {
            const response = await fetch(analyticsUrl + '?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load analytics');
            }

            const payload = await response.json();
            activeRange = payload.range.key;

            els.rangeSubtitle.textContent = payload.range.label;
            setPillActive(activeRange);

            payload.revenue_chart.total_formatted = payload.revenue_chart.total_formatted || payload.stats.period_sales_formatted;
            renderRevenueChart(payload.revenue_chart);
            renderStockChart(payload.stock_chart);
            updateStats(payload.stats);

            const url = new URL(dashboardUrl);
            params.forEach(function (value, key) {
                url.searchParams.set(key, value);
            });
            window.history.replaceState({}, '', url.toString());
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    }

    els.pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            const key = pill.dataset.range;
            setPillActive(key);
            if (key === 'custom') {
                return;
            }
            fetchAnalytics(key);
        });
    });

    els.applyCustom.addEventListener('click', function () {
        fetchAnalytics('custom');
    });

    initialRevenue.total_formatted = @json(format_money($revenueChart['total']));
    renderRevenueChart(initialRevenue);
    renderStockChart(initialStock);
})();
</script>
@endpush
@endif
