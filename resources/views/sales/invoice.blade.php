@extends('layouts.print')

@section('title', 'Invoice ' . $sale->sale_number)

@section('content')
@if(session('success'))
    <div class="no-print mx-auto mb-4 max-w-md rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('success') }}
    </div>
@endif

<div class="mx-auto max-w-md text-sm">
    <div class="border-b border-gray-200 pb-4 text-center">
        <h1 class="text-lg font-bold text-gray-900">{{ $business->name }}</h1>
        @if($business->address)
            <p class="mt-1 text-xs text-gray-500">{{ $business->address }}</p>
        @endif
        @if($business->phone)
            <p class="text-xs text-gray-500">{{ $business->phone }}</p>
        @endif
        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-amber-700">Tax Invoice</p>
        <p class="font-bold text-gray-900">{{ $sale->sale_number }}</p>
        <p class="text-xs text-gray-500">{{ optional($sale->completed_at)->format('M j, Y g:i A') }}</p>
    </div>

    <div class="my-4 grid grid-cols-2 gap-3 text-xs">
        <div>
            <p class="uppercase text-gray-500">Cashier</p>
            <p class="font-medium text-gray-900">{{ optional($sale->user)->name ?? '—' }}</p>
        </div>
        @if($sale->customer)
            <div>
                <p class="uppercase text-gray-500">Bill to</p>
                <p class="font-medium text-gray-900">{{ $sale->customer->name }}</p>
                @if($sale->customer->phone)
                    <p class="text-gray-600">{{ $sale->customer->phone }}</p>
                @endif
            </div>
        @endif
    </div>

    <table class="mb-4 w-full text-xs">
        <thead>
            <tr class="border-b border-gray-200 text-left uppercase text-gray-500">
                <th class="pb-2">Item</th>
                <th class="pb-2 text-center">Qty</th>
                <th class="pb-2 text-right">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($sale->items as $item)
                <tr>
                    <td class="py-2 pr-2">
                        <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                        @if($item->notes)
                            <p class="text-orange-700">Note: {{ $item->notes }}</p>
                        @endif
                    </td>
                    <td class="py-2 text-center">{{ format_unit_quantity($item->quantity, $item->measurement_unit ?? 'piece', $business->id) }}</td>
                    <td class="py-2 text-right">@money($item->subtotal)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="space-y-1 border-t border-gray-200 pt-3 text-xs">
        <div class="flex justify-between">
            <span class="text-gray-600">Subtotal</span>
            <span>@money($sale->subtotal)</span>
        </div>
        @if($sale->discount_amount > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Discount</span>
                <span>-@money($sale->discount_amount)</span>
            </div>
        @endif
        @if($sale->tax_amount > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Tax</span>
                <span>@money($sale->tax_amount)</span>
            </div>
        @endif
        <div class="flex justify-between text-base font-bold text-gray-900">
            <span>Amount</span>
            <span>@money($sale->total)</span>
        </div>
        @if($sale->customer)
            <div class="flex justify-between pt-1">
                <span class="text-gray-600">Customer balance</span>
                <span class="font-medium">@money($sale->customer->outstanding_balance)</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Credit limit</span>
                <span>@money($sale->customer->credit_limit)</span>
            </div>
        @endif
    </div>

    <p class="mt-4 text-center text-sm font-semibold text-gray-800">Thank you for supporting us</p>

    @if($sale->notes)
        <p class="mt-3 text-xs text-gray-500">Note: {{ $sale->notes }}</p>
    @endif
</div>

<x-document-share-toolbar
    layout="panel"
    print-label="Print"
    :whats-app-href="$whatsAppUrl"
    :whats-app-message="$invoiceMessage"
    :default-phone="$defaultPhone"
    :show-phone-input="true"
    :email-href="$emailShareUrl"
>
    @if(\App\Support\SaleDocument::hasCompanionReceipt($sale))
        <div class="mt-3 w-full">
            <a href="{{ \App\Support\SaleDocument::receiptUrl($sale) }}" target="_blank"
               class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                View receipt
            </a>
        </div>
    @endif
</x-document-share-toolbar>
@endsection
