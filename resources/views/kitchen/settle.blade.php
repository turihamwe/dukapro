@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.restaurant-staff')

@section('title', 'Collect Payment')
@section('container_class', 'max-w-lg')

@section('content')
<x-page-header title="Collect Payment" :subtitle="$order->order_number . ' · ' . \App\Enums\KitchenOrderStatus::label($order->status)" class="!mb-4">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.kitchen.ready') }}">Back</x-button>
    </x-slot>
</x-page-header>

<x-card>
    <div class="mb-4 rounded-xl bg-gray-50 p-4 text-sm">
        <p class="font-semibold text-gray-900">{{ $order->tableDisplay() }} · {{ $order->waiter->name ?? 'Waiter' }}</p>
        <ul class="mt-2 space-y-1 text-gray-600">
            @foreach($order->items as $item)
                <li>
                    {{ $item->quantity }}× {{ $item->product_name }} — @money($item->subtotal)
                    @if($item->notes)
                        <span class="block text-xs text-orange-700">Note: {{ $item->notes }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="mt-3 text-lg font-bold text-gray-900">Total: @money($order->subtotal)</p>
    </div>

    <form method="POST" action="{{ tenant_route('tenant.kitchen.settle.store', ['kitchenOrder' => $order]) }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Payment method</label>
            <select name="payment_method" id="payment-method" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank">Bank / other</option>
                @if($business->usesShiftWaiterMode())
                    <option value="credit">Credit tab (unpaid)</option>
                @endif
            </select>
        </div>
        <div id="mobile-provider-wrap" class="hidden">
            <label class="mb-1 block text-sm font-medium text-gray-700">Mobile provider</label>
            <select name="mobile_money_provider" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="mtn">MTN</option>
                <option value="airtel">Airtel</option>
            </select>
        </div>
        <x-button variant="primary" type="submit" class="w-full">Complete sale</x-button>
    </form>
</x-card>
@endsection

@push('scripts')
<script>
(function () {
    var select = document.getElementById('payment-method');
    var wrap = document.getElementById('mobile-provider-wrap');
    select.addEventListener('change', function () {
        wrap.classList.toggle('hidden', select.value !== 'mobile_money');
    });
})();
</script>
@endpush
