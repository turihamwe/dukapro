@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.restaurant-staff')

@section('title', 'Collect Payment')
@section('main_class', 'lg:!py-2')

@section('content')
@php use App\Enums\KitchenOrderStatus; @endphp

<x-page-header title="Collect Payment" subtitle="Unpaid kitchen orders — collect anytime, including cash-upfront." class="!mb-4" />

<div class="space-y-2">
    @forelse($orders as $order)
        <x-card class="flex flex-wrap items-center justify-between gap-3 !p-3 sm:!p-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-gray-900">{{ $order->tableDisplay() }} · {{ $order->order_number }}</p>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-800">
                        {{ KitchenOrderStatus::label($order->status) }}
                    </span>
                </div>
                <p class="text-xs text-gray-500">{{ $order->waiter->name ?? 'Waiter' }} · {{ $order->items->count() }} items</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <p class="text-lg font-bold text-gray-900">@money($order->subtotal)</p>
                <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.kitchen.settle', ['kitchenOrder' => $order]) }}">Collect payment</x-button>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No unpaid kitchen orders right now.</x-card>
    @endforelse
</div>
@endsection
