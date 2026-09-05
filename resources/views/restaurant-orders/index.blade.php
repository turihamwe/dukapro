@extends('layouts.cashier')

@section('title', 'Orders')
@section('main_class', 'lg:!py-2')

@section('content')
@php use App\Enums\KitchenOrderStatus; @endphp

<x-page-header title="Today's Orders" subtitle="Print receipts for paid orders or invoices for unpaid tabs." class="!mb-4" />

<div class="space-y-3">
    @forelse($orders as $order)
        <x-card class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-gray-900">{{ $order->order_number }}</p>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                        {{ $order->isPaid() ? 'Paid' : KitchenOrderStatus::label($order->status) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-600">{{ $order->tableDisplay() }} · {{ $order->waiter->name ?? 'Staff' }}</p>
                <ul class="mt-2 space-y-1 text-xs text-gray-500">
                    @foreach($order->items as $item)
                        <li>
                            {{ $item->quantity }}× {{ $item->product_name }}
                            @if($item->notes)
                                <span class="text-orange-700">({{ $item->notes }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-2">
                <p class="text-lg font-bold text-gray-900">@money($order->subtotal)</p>
                <a href="{{ tenant_route('tenant.restaurant-orders.print', ['kitchenOrder' => $order]) }}" target="_blank"
                   class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                    Print {{ $order->isPaid() ? 'receipt' : 'invoice' }}
                </a>
                @if($order->awaitsPayment())
                    <a href="{{ tenant_route('tenant.kitchen.settle', ['kitchenOrder' => $order]) }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">Collect payment →</a>
                @endif
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No orders placed today.</x-card>
    @endforelse
</div>
@endsection
