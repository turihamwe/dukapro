@php
    $financial = $financialStatistics ?? null;
@endphp
@if($financial)
<section id="financial-statistics" class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Financial statistics</h2>
            <p id="financial-range-label" class="mt-0.5 text-sm text-gray-500">{{ $financial['label'] }}</p>
        </div>
        <form id="financial-filters" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div>
                <label for="financial-range" class="mb-1 block text-xs font-medium text-gray-600">Period</label>
                <select id="financial-range" name="range" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-40">
                    <option value="day" @selected($financial['range'] === 'day')>Today</option>
                    <option value="week" @selected($financial['range'] === 'week')>This Week</option>
                    <option value="month" @selected($financial['range'] === 'month')>This Month</option>
                    <option value="year" @selected($financial['range'] === 'year')>This Year</option>
                    <option value="custom" @selected($financial['range'] === 'custom')>Custom Range</option>
                </select>
            </div>
            <div id="financial-custom-dates" class="flex flex-col gap-3 sm:flex-row {{ $financial['range'] === 'custom' ? '' : 'hidden' }}">
                <div>
                    <label for="financial-from" class="mb-1 block text-xs font-medium text-gray-600">Start date</label>
                    <input type="date" id="financial-from" name="from" value="{{ $financial['from'] }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label for="financial-to" class="mb-1 block text-xs font-medium text-gray-600">End date</label>
                    <input type="date" id="financial-to" name="to" value="{{ $financial['to'] }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>
            @if($financial['show_branch_picker'])
                <div>
                    <label for="financial-branch" class="mb-1 block text-xs font-medium text-gray-600">Branch</label>
                    <select id="financial-branch" name="branch_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-48">
                        <option value="all" @selected($financial['branch_id'] === 'all')>All Branches</option>
                        @foreach($financial['branches'] as $branchOption)
                            <option value="{{ $branchOption['id'] }}" @selected((string) $financial['branch_id'] === (string) $branchOption['id'])>{{ $branchOption['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </form>
    </div>

    <p id="financial-error" class="mt-3 hidden text-sm text-red-600">Could not refresh these figures. Try again.</p>

    <div id="financial-body" class="mt-5">
        <div id="financial-kpi-grid" class="grid grid-cols-2 gap-3 {{ $financial['include_purchases'] ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-800">Total Sales</p>
                <p id="financial-sales" class="mt-1 text-lg font-bold text-emerald-900">{{ $financial['totals']['sales_formatted'] }}</p>
            </div>
            <div id="financial-purchases-card" class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 {{ $financial['include_purchases'] ? '' : 'hidden' }}">
                <p class="text-xs font-medium uppercase tracking-wide text-blue-800">Total Purchases</p>
                <p id="financial-purchases" class="mt-1 text-lg font-bold text-blue-900">{{ $financial['totals']['purchases_formatted'] }}</p>
            </div>
            <div class="rounded-xl border border-orange-100 bg-orange-50/60 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-orange-800">Total Expenses</p>
                <p id="financial-expenses" class="mt-1 text-lg font-bold text-orange-900">{{ $financial['totals']['expenses_formatted'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Net Cash Impact</p>
                <p id="financial-net" class="mt-1 text-lg font-bold {{ $financial['totals']['net'] < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $financial['totals']['net_formatted'] }}</p>
            </div>
        </div>

        <div class="relative mt-6 h-72 sm:h-80">
            <canvas id="financial-chart" aria-label="Sales, purchases, and expenses"></canvas>
        </div>
    </div>
</section>
@push('scripts')
<script>
(function () {
    var endpoint = @json(tenant_route('tenant.dashboard.financial-statistics'));
    var payload = @json($financial);
    var chart = null;

    function money(amount, sample) {
        var formatted = Number(amount).toLocaleString(undefined, { maximumFractionDigits: 0 });
        if (amount < 0) {
            formatted = '-' + Math.abs(Number(amount)).toLocaleString(undefined, { maximumFractionDigits: 0 });
        }
        return String(sample || '').replace(/-?[\d,.]+/, formatted);
    }

    function setNetColor(amount) {
        var el = document.getElementById('financial-net');
        if (!el) return;
        el.classList.toggle('text-red-600', amount < 0);
        el.classList.toggle('text-gray-900', amount >= 0);
    }

    function chartDatasets(data) {
        var sets = [
            { label: 'Sales', data: data.chart.sales, backgroundColor: '#059669', borderRadius: 4, maxBarThickness: 28 }
        ];
        if (data.include_purchases) {
            sets.push({ label: 'Purchases', data: data.chart.purchases, backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 28 });
        }
        sets.push({ label: 'Expenses', data: data.chart.expenses, backgroundColor: '#ea580c', borderRadius: 4, maxBarThickness: 28 });
        return sets;
    }

    function applyPayload(data) {
        payload = data;
        document.getElementById('financial-range-label').textContent = data.label;
        document.getElementById('financial-sales').textContent = data.totals.sales_formatted;
        var purchasesCard = document.getElementById('financial-purchases-card');
        var purchasesValue = document.getElementById('financial-purchases');
        var grid = document.getElementById('financial-kpi-grid');
        if (purchasesCard) purchasesCard.classList.toggle('hidden', !data.include_purchases);
        if (purchasesValue && data.include_purchases) purchasesValue.textContent = data.totals.purchases_formatted;
        if (grid) {
            grid.classList.toggle('xl:grid-cols-4', !!data.include_purchases);
            grid.classList.toggle('xl:grid-cols-3', !data.include_purchases);
        }
        document.getElementById('financial-expenses').textContent = data.totals.expenses_formatted;
        document.getElementById('financial-net').textContent = data.totals.net_formatted;
        setNetColor(data.totals.net);
        drawChart(data);
    }

    function drawChart(data) {
        if (!window.Chart) return;
        var canvas = document.getElementById('financial-chart');
        if (!canvas) return;
        var sample = data.currency_sample;
        var datasets = chartDatasets(data);
        if (chart) {
            chart.data.labels = data.chart.labels;
            chart.data.datasets = datasets;
            chart.update();
            return;
        }
        chart = new window.Chart(canvas, {
            type: 'bar',
            data: { labels: data.chart.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + money(context.parsed.y, sample);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return money(value, sample);
                            }
                        }
                    }
                }
            }
        });
    }

    function boot() {
        drawChart(payload);
    }

    function ensureChart(done) {
        if (window.Chart) {
            done();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
        script.onload = done;
        document.head.appendChild(script);
    }

    function selectedRange() {
        return document.getElementById('financial-range').value;
    }

    function toggleCustomDates() {
        var panel = document.getElementById('financial-custom-dates');
        if (panel) panel.classList.toggle('hidden', selectedRange() !== 'custom');
    }

    function reload() {
        var params = new URLSearchParams();
        params.set('range', selectedRange());
        if (selectedRange() === 'custom') {
            params.set('from', document.getElementById('financial-from').value);
            params.set('to', document.getElementById('financial-to').value);
            if (!params.get('from') || !params.get('to')) return;
        }
        var branch = document.getElementById('financial-branch');
        if (branch) params.set('branch_id', branch.value);
        var body = document.getElementById('financial-body');
        var error = document.getElementById('financial-error');
        if (body) body.style.opacity = '0.55';
        if (error) error.classList.add('hidden');
        fetch(endpoint + '?' + params.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('refresh failed');
                return response.json();
            })
            .then(function (data) {
                applyPayload(data);
            })
            .catch(function () {
                if (error) error.classList.remove('hidden');
            })
            .finally(function () {
                if (body) body.style.opacity = '1';
            });
    }

    document.getElementById('financial-range').addEventListener('change', function () {
        toggleCustomDates();
        if (selectedRange() !== 'custom') reload();
    });
    var fromInput = document.getElementById('financial-from');
    var toInput = document.getElementById('financial-to');
    if (fromInput) fromInput.addEventListener('change', reload);
    if (toInput) toInput.addEventListener('change', reload);
    var branchInput = document.getElementById('financial-branch');
    if (branchInput) branchInput.addEventListener('change', reload);

    ensureChart(boot);
})();
</script>
@endpush
@endif
