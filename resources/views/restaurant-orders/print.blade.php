@extends('layouts.print')

@section('title', ucfirst($documentType))

@section('content')
<div class="text-center border-b border-gray-200 pb-4 mb-6">
    <h1 class="text-xl font-bold">{{ $business->name }}</h1>
    <p class="mt-1 text-sm text-gray-600">{{ ucfirst($documentType) }} · {{ $order->order_number }}</p>
    <p class="text-xs text-gray-500">{{ optional($order->placed_at)->format('M j, Y g:i A') }}</p>
</div>

<div class="mb-6 grid grid-cols-2 gap-4 text-sm">
    <div>
        <p class="text-xs uppercase text-gray-500">Table / area</p>
        <p class="font-medium">{{ $order->tableDisplay() }}</p>
    </div>
    <div>
        <p class="text-xs uppercase text-gray-500">Server</p>
        <p class="font-medium">{{ $order->waiter->name ?? '—' }}</p>
    </div>
</div>

<table class="mb-6 w-full text-sm">
    <thead>
        <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
            <th class="pb-2">Item</th>
            <th class="pb-2 text-center">Qty</th>
            <th class="pb-2 text-right">Amount</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @foreach($order->items as $item)
            <tr>
                <td class="py-2 pr-2">
                    <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                    @if($item->notes)
                        <p class="text-xs text-orange-700">Note: {{ $item->notes }}</p>
                    @endif
                </td>
                <td class="py-2 text-center">{{ $item->quantity }}</td>
                <td class="py-2 text-right">@money($item->subtotal)</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="border-t-2 border-gray-900">
            <td colspan="2" class="pt-3 text-right font-semibold">Total</td>
            <td class="pt-3 text-right text-lg font-bold">@money($order->subtotal)</td>
        </tr>
    </tfoot>
</table>

@if($documentType === 'receipt' && $order->sale)
    <div class="rounded-lg bg-gray-50 p-4 text-sm">
        <p><span class="text-gray-500">Sale #:</span> {{ $order->sale->sale_number }}</p>
        <p><span class="text-gray-500">Payment:</span> {{ ucfirst(str_replace('_', ' ', $order->sale->payment_method)) }}</p>
        <p class="mt-2 font-semibold text-emerald-700">PAID — Thank you!</p>
    </div>
@else
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold">INVOICE — Payment due</p>
        <p class="mt-1 text-xs">Present this bill to the cashier when ready to pay.</p>
    </div>
@endif

@if($order->notes)
    <p class="mt-4 text-xs text-gray-500">Order note: {{ $order->notes }}</p>
@endif
@endsection
