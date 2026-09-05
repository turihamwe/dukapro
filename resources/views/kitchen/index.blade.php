@extends(auth()->user()->isChef() ? 'layouts.restaurant-staff' : (auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin'))

@section('title', 'Kitchen')
@section('main_class', 'lg:!py-2')

@section('content')
@php use App\Enums\KitchenOrderStatus; @endphp

<x-page-header title="Kitchen Display" subtitle="Branch orders — update status as you prepare." class="!mb-4">
    <x-slot name="actions">
        <span id="kitchen-sync" class="text-xs text-gray-400">Live</span>
    </x-slot>
</x-page-header>

<div id="kitchen-board" class="grid gap-4 md:grid-cols-3">
    @foreach([KitchenOrderStatus::PENDING_KITCHEN, KitchenOrderStatus::PREPARING, KitchenOrderStatus::READY] as $columnStatus)
        @php $columnOrders = $orders->where('status', $columnStatus); @endphp
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm" data-column="{{ $columnStatus }}">
            <header class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ KitchenOrderStatus::label($columnStatus) }}</h2>
                <p class="text-xs text-gray-500"><span class="column-count">{{ $columnOrders->count() }}</span> orders</p>
            </header>
            <div class="column-body max-h-[70vh] space-y-3 overflow-y-auto p-3">
                @foreach($columnOrders as $order)
                    @include('kitchen.partials.order-card', ['order' => $order])
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
(function () {
    var pollUrl = @json(tenant_route('tenant.kitchen.poll'));
    var csrf = @json(csrf_token());
    var board = document.getElementById('kitchen-board');

    function nextAction(status) {
        if (status === 'pending_kitchen') return { label: 'Start preparing', next: 'preparing' };
        if (status === 'preparing') return { label: 'Mark ready', next: 'ready' };
        return null;
    }

    function renderOrder(order) {
        var action = nextAction(order.status);
        var items = (order.items || []).map(function (item) {
            var note = item.notes ? '<span class="block font-medium text-orange-700">↳ ' + item.notes + '</span>' : '';
            return '<li class="flex justify-between gap-2 text-xs"><span class="text-gray-700">' + item.quantity + '× ' + item.product_name + note + '</span></li>';
        }).join('');

        var actionHtml = action
            ? '<form method="POST" action="' + order.update_url + '" class="mt-3"><input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="status" value="' + action.next + '"><button type="submit" class="w-full rounded-lg bg-orange-600 px-3 py-2 text-xs font-semibold text-white hover:bg-orange-700">' + action.label + '</button></form>'
            : '';

        var cancelHtml = order.status !== 'ready'
            ? '<form method="POST" action="' + order.update_url + '" class="mt-2"><input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="status" value="cancelled"><button type="submit" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600">Cancel</button></form>'
            : '';

        var paidBadge = order.is_paid
            ? '<span class="mb-1 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-emerald-800">Paid</span>'
            : '';

        return '<article class="order-card rounded-xl border border-gray-200 bg-gray-50 p-3" data-status="' + order.status + '" data-id="' + order.id + '">' +
            '<div class="flex items-start justify-between gap-2"><div><p class="text-sm font-bold text-gray-900">' + (order.table_label || 'Walk-in') + '</p>' +
            '<p class="text-xs text-gray-500">' + order.order_number + ' · ' + (order.waiter || 'Waiter') + '</p></div>' +
            '<div class="text-right">' + paidBadge + '<p class="text-sm font-semibold text-gray-900">' + Number(order.subtotal).toLocaleString() + '</p></div></div>' +
            '<ul class="mt-2 space-y-1">' + items + '</ul>' + actionHtml + cancelHtml + '</article>';
    }

    function renderBoard(orders) {
        var columns = {
            pending_kitchen: board.querySelector('[data-column="pending_kitchen"] .column-body'),
            preparing: board.querySelector('[data-column="preparing"] .column-body'),
            ready: board.querySelector('[data-column="ready"] .column-body')
        };

        Object.keys(columns).forEach(function (status) {
            var body = columns[status];
            var filtered = orders.filter(function (o) { return o.status === status; });
            body.innerHTML = filtered.map(renderOrder).join('');
            board.querySelector('[data-column="' + status + '"] .column-count').textContent = filtered.length;
        });
    }

    function poll() {
        fetch(pollUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderBoard(data.orders || []); })
            .catch(function () {});
    }

    setInterval(poll, 12000);
})();
</script>
@endpush
