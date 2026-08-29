@php
    $modals = [
        'drill-inventory-value' => ['title' => 'Stock value by product (cost)', 'items' => $drilldown['inventory_value'] ?? []],
        'drill-retail-value' => ['title' => 'Retail stock value by product', 'items' => $drilldown['retail_stock_value'] ?? []],
        'drill-potential-profit' => ['title' => 'Potential profit by product', 'items' => $drilldown['potential_profit'] ?? []],
        'drill-todays-sales' => ['title' => "Today's completed sales", 'items' => $drilldown['todays_sales'] ?? []],
        'drill-low-stock' => ['title' => 'Low stock products', 'items' => $drilldown['low_stock'] ?? []],
        'drill-products' => ['title' => 'Active products', 'items' => $drilldown['products'] ?? []],
    ];
@endphp

@foreach($modals as $id => $modal)
    <div id="{{ $id }}" class="dashboard-drilldown-modal fixed inset-0 z-[100] hidden items-end justify-center bg-black/40 p-4 sm:items-center" role="dialog" aria-modal="true">
        <div class="max-h-[80vh] w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ $modal['title'] }}</h3>
                <button type="button" class="dashboard-drilldown-close rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="max-h-[60vh] overflow-y-auto px-5 py-3">
                @forelse($modal['items'] as $row)
                    <div class="flex items-start justify-between gap-3 border-b border-gray-50 py-3 last:border-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $row['title'] }}</p>
                            @if(!empty($row['meta']))
                                <p class="text-xs text-gray-500">{{ $row['meta'] }}</p>
                            @endif
                        </div>
                        @if(!empty($row['value']))
                            <p class="shrink-0 text-sm font-semibold text-gray-900">{{ $row['value'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">No records to show.</p>
                @endforelse
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
(function () {
    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!document.querySelector('.dashboard-drilldown-modal.flex')) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.querySelectorAll('.dashboard-drilldown-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-modal'));
        });
    });

    document.querySelectorAll('.dashboard-drilldown-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(btn.closest('.dashboard-drilldown-modal'));
        });
    });

    document.querySelectorAll('.dashboard-drilldown-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal(modal);
        });
    });
})();
</script>
@endpush
