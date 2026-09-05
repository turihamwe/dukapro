@php use App\Enums\KitchenOrderStatus; @endphp
<article class="order-card rounded-xl border border-gray-200 bg-gray-50 p-3" data-status="{{ $order->status }}" data-id="{{ $order->id }}">
    <div class="flex items-start justify-between gap-2">
        <div>
            <p class="text-sm font-bold text-gray-900">{{ $order->tableDisplay() }}</p>
            <p class="text-xs text-gray-500">{{ $order->order_number }} · {{ $order->waiter->name ?? 'Waiter' }}</p>
        </div>
        <div class="text-right">
            @if($order->isPaid())
                <span class="mb-1 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-emerald-800">Paid</span>
            @endif
            <p class="text-sm font-semibold text-gray-900">@money($order->subtotal)</p>
        </div>
    </div>
    <ul class="mt-2 space-y-1">
        @foreach($order->items as $item)
            <li class="flex justify-between gap-2 text-xs">
                <span class="text-gray-700">
                    {{ $item->quantity }}× {{ $item->product_name }}
                    @if($item->notes)
                        <span class="block font-medium text-orange-700">↳ {{ $item->notes }}</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
    @if($order->status === KitchenOrderStatus::PENDING_KITCHEN)
        <form method="POST" action="{{ tenant_route('tenant.kitchen.update-status', ['kitchenOrder' => $order]) }}" class="mt-3">
            @csrf
            <input type="hidden" name="status" value="preparing">
            <button type="submit" class="w-full rounded-lg bg-orange-600 px-3 py-2 text-xs font-semibold text-white hover:bg-orange-700">Start preparing</button>
        </form>
    @elseif($order->status === KitchenOrderStatus::PREPARING)
        <form method="POST" action="{{ tenant_route('tenant.kitchen.update-status', ['kitchenOrder' => $order]) }}" class="mt-3">
            @csrf
            <input type="hidden" name="status" value="ready">
            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Mark ready</button>
        </form>
    @endif
    @if($order->status !== KitchenOrderStatus::READY)
        <form method="POST" action="{{ tenant_route('tenant.kitchen.update-status', ['kitchenOrder' => $order]) }}" class="mt-2">
            @csrf
            <input type="hidden" name="status" value="cancelled">
            <button type="submit" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600">Cancel</button>
        </form>
    @endif
</article>
