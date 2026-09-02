@extends('layouts.cashier')

@section('title', $waiter->name . ' — Shift Orders')

@section('content')
<div class="mb-6">
    <a href="{{ tenant_route('tenant.waiter-shift.index', ['date' => $date->toDateString()]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Back to shift balancing</a>
    <h1 class="mt-2 text-xl font-bold text-gray-900">{{ $waiter->name }}</h1>
    <p class="text-sm text-gray-500">{{ $date->format('l, M j, Y') }} · {{ $summary['order_count'] }} orders</p>
</div>

<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
    <x-card class="!p-4"><p class="text-xs text-gray-500">Cash</p><p class="text-lg font-bold">@money($summary['expected_cash'])</p></x-card>
    <x-card class="!p-4"><p class="text-xs text-gray-500">Mobile (Airtel/MTN)</p><p class="text-lg font-bold">@money($summary['expected_mobile_airtel'] + $summary['expected_mobile_mtn'])</p></x-card>
    <x-card class="!p-4"><p class="text-xs text-gray-500">Open credit</p><p class="text-lg font-bold text-amber-700">@money($summary['expected_credit'])</p></x-card>
</div>

<x-card :padding="false" class="overflow-hidden">
    <div class="border-b border-gray-100 px-4 py-3 sm:px-5">
        <h2 class="font-semibold text-gray-900">Order history</h2>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($summary['sales'] as $sale)
            <div class="px-4 py-4 sm:px-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-medium text-gray-900">#{{ $sale->sale_number }}</p>
                        <p class="text-xs text-gray-500">{{ optional($sale->completed_at)->format('g:i A') }} · {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}
                            @if($sale->mobile_money_provider)
                                ({{ strtoupper($sale->mobile_money_provider) }})
                            @endif
                        </p>
                    </div>
                    <p class="text-lg font-bold">@money($sale->total)</p>
                </div>

                @if($sale->is_credit_sale && ! $sale->credit_settled_at)
                    <form method="POST" action="{{ tenant_route('tenant.waiter-shift.settle-credit', ['sale' => $sale->id]) }}" class="mt-3 flex flex-wrap items-end gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        @csrf
                        <div class="min-w-[140px] flex-1">
                            <label class="mb-1 block text-xs font-medium text-amber-900">Settle tab via</label>
                            <select name="settlement_method" required class="w-full rounded-lg border-amber-200 px-2 py-1.5 text-sm">
                                <option value="cash">Cash</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="bank">Merchant / Bank</option>
                            </select>
                        </div>
                        <div class="min-w-[160px] flex-1">
                            <label class="mb-1 block text-xs font-medium text-amber-900">Notes</label>
                            <input type="text" name="notes" placeholder="Optional" class="w-full rounded-lg border-amber-200 px-2 py-1.5 text-sm">
                        </div>
                        <button type="submit" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-500">Mark settled</button>
                    </form>
                @elseif($sale->is_credit_sale && $sale->credit_settled_at)
                    <p class="mt-2 text-xs text-emerald-700">Credit settled {{ $sale->credit_settled_at->format('g:i A') }} via {{ str_replace('_', ' ', $sale->credit_settlement_method) }}</p>
                @endif
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-gray-500">No orders for this waiter on the selected date.</p>
        @endforelse
    </div>
</x-card>
@endsection
