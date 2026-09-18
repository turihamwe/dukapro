@php
    $fromYear = $fromYear ?? ($sales['from_year'] ?? 2026);
    $fromYearOptions = $sales['from_year_options'] ?? [2026];
@endphp

<section id="funnel-trend-chart">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Funnel growth over time</h2>
            <p class="mt-1 text-sm text-gray-500">Cumulative registered, catalog, and active subscribed businesses</p>
        </div>
        <form method="GET" action="{{ route('superadmin.business-sales.index') }}" class="flex flex-wrap items-center gap-2">
            <label for="from_year" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Starting year</label>
            <select id="from_year" name="from_year"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-800 focus:border-indigo-500 focus:outline-none"
                    onchange="this.form.submit()">
                @foreach($fromYearOptions as $year)
                    <option value="{{ $year }}" @if((int) $fromYear === (int) $year) selected @endif>{{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center gap-4 text-xs">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-slate-600"></span>
                <span class="font-medium text-gray-700">Registered</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                <span class="font-medium text-gray-700">Active catalog</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-violet-600"></span>
                <span class="font-medium text-gray-700">Subscribed</span>
            </div>
            <span id="funnel-trend-range-label" class="text-gray-400"></span>
        </div>

        <div class="relative h-72 sm:h-80">
            <canvas id="business-sales-funnel-chart" aria-label="Business funnel trend chart"></canvas>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'annual' => 'Annual'] as $key => $label)
                <button type="button"
                        data-chart-period="{{ $key }}"
                        class="funnel-chart-period-btn rounded-lg border px-3 py-1.5 text-xs font-semibold transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <p class="mt-3 text-xs text-gray-500">Subscribed counts reflect businesses with an active paid subscription at each point in time, reconstructed from completed payments.</p>
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var trendCharts = @json($sales['trend_charts'] ?? []);
    var activePeriod = 'monthly';
    var funnelChart = null;
    var periodButtons = document.querySelectorAll('.funnel-chart-period-btn');
    var rangeLabel = document.getElementById('funnel-trend-range-label');

    function setActiveButton(period) {
        periodButtons.forEach(function (button) {
            var isActive = button.getAttribute('data-chart-period') === period;
            button.classList.toggle('bg-indigo-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-indigo-600', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
            button.classList.toggle('border-gray-200', !isActive);
            button.classList.toggle('hover:bg-gray-50', !isActive);
        });
    }

    function renderChart(period) {
        var canvas = document.getElementById('business-sales-funnel-chart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var data = trendCharts[period] || trendCharts.monthly;
        if (!data) {
            return;
        }

        activePeriod = period;
        setActiveButton(period);

        if (rangeLabel) {
            rangeLabel.textContent = data.label || '';
        }

        if (funnelChart) {
            funnelChart.destroy();
        }

        funnelChart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Registered',
                        data: data.registered || [],
                        borderColor: '#475569',
                        backgroundColor: 'rgba(71, 85, 105, 0.08)',
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        tension: 0.25,
                        fill: false,
                    },
                    {
                        label: 'Active catalog',
                        data: data.catalog || [],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        tension: 0.25,
                        fill: false,
                    },
                    {
                        label: 'Subscribed',
                        data: data.subscribed || [],
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.08)',
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        tension: 0.25,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var value = context.parsed.y || 0;
                                return context.dataset.label + ': ' + new Intl.NumberFormat().format(value);
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 14,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.2)',
                        },
                    },
                },
            },
        });
    }

    periodButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            renderChart(button.getAttribute('data-chart-period') || 'monthly');
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        renderChart(activePeriod);
    });
})();
</script>
@endpush
