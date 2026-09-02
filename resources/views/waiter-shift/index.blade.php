@extends('layouts.cashier')

@section('title', 'Waiter Shift Balancing')

@section('content')
<x-page-header
    title="Waiter Shift Balancing"
    subtitle="Balance floor staff collections for {{ $date->format('M j, Y') }}"
/>

<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Shift date</label>
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>
    <x-button type="submit" variant="secondary" size="sm">Load</x-button>
</form>

@if($shift['rows']->isEmpty())
    <x-card>
        <p class="text-sm text-gray-600">No waiter orders recorded for this date yet. Assign waiters at POS when completing sales.</p>
    </x-card>
@else
    <form method="POST" action="{{ tenant_route('tenant.waiter-shift.balance-all') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="shift_date" value="{{ $date->toDateString() }}">

        <div class="grid gap-3">
            @foreach($shift['rows'] as $index => $row)
                @php
                    $waiter = $row['waiter'];
                    $summary = $row['summary'];
                    $balance = $row['balance'];
                @endphp
                <x-card :padding="false" class="overflow-hidden">
                    <div class="border-b border-gray-100 bg-gray-50 px-4 py-3 sm:px-5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $waiter->name }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($waiter->role) }} · {{ $summary['order_count'] }} orders</p>
                            </div>
                            <a href="{{ tenant_route('tenant.waiter-shift.show', ['waiter' => $waiter->id, 'date' => $date->toDateString()]) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View orders →</a>
                        </div>
                    </div>

                    <input type="hidden" name="waiters[{{ $index }}][waiter_user_id]" value="{{ $waiter->id }}">

                    <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                        <div class="space-y-2 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Expected</p>
                            <div class="flex justify-between"><span>Cash</span><span class="font-medium">@money($summary['expected_cash'])</span></div>
                            <div class="flex justify-between"><span>Airtel Money</span><span class="font-medium">@money($summary['expected_mobile_airtel'])</span></div>
                            <div class="flex justify-between"><span>MTN MoMo</span><span class="font-medium">@money($summary['expected_mobile_mtn'])</span></div>
                            <div class="flex justify-between"><span>Merchant / Bank</span><span class="font-medium">@money($summary['expected_bank_other'])</span></div>
                            <div class="flex justify-between text-amber-800"><span>Open credit tabs</span><span class="font-semibold">@money($summary['expected_credit'])</span></div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted by waiter</p>
                            <x-input type="number" step="0.01" name="waiters[{{ $index }}][actual_cash]" label="Cash collected"
                                     value="{{ old('waiters.'.$index.'.actual_cash', optional($balance)->actual_cash ?? 0) }}" />
                            <div class="grid grid-cols-2 gap-2">
                                <x-input type="number" step="0.01" name="waiters[{{ $index }}][actual_mobile_airtel]" label="Airtel"
                                         value="{{ old('waiters.'.$index.'.actual_mobile_airtel', optional($balance)->actual_mobile_airtel ?? 0) }}" />
                                <x-input type="number" step="0.01" name="waiters[{{ $index }}][actual_mobile_mtn]" label="MTN"
                                         value="{{ old('waiters.'.$index.'.actual_mobile_mtn', optional($balance)->actual_mobile_mtn ?? 0) }}" />
                            </div>
                            <x-input type="number" step="0.01" name="waiters[{{ $index }}][actual_bank_other]" label="Merchant / Bank"
                                     value="{{ old('waiters.'.$index.'.actual_bank_other', optional($balance)->actual_bank_other ?? 0) }}" />
                            <x-input type="number" step="0.01" name="waiters[{{ $index }}][actual_credit_collected]" label="Credit settled"
                                     value="{{ old('waiters.'.$index.'.actual_credit_collected', optional($balance)->actual_credit_collected ?? 0) }}" />
                            <x-input type="text" name="waiters[{{ $index }}][notes]" label="Shortage notes"
                                     value="{{ old('waiters.'.$index.'.notes', optional($balance)->notes) }}" placeholder="Explain variance..." />
                            @if($balance)
                                <p class="text-xs {{ $balance->shortage >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                    Shortage: @money($balance->shortage)
                                </p>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-950">
            <p class="font-semibold">Shift totals (expected)</p>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5">
                <div><span class="text-xs text-indigo-800/70">Cash</span><p class="font-bold">@money($shift['totals']['expected_cash'])</p></div>
                <div><span class="text-xs text-indigo-800/70">Airtel</span><p class="font-bold">@money($shift['totals']['expected_mobile_airtel'])</p></div>
                <div><span class="text-xs text-indigo-800/70">MTN</span><p class="font-bold">@money($shift['totals']['expected_mobile_mtn'])</p></div>
                <div><span class="text-xs text-indigo-800/70">Merchant</span><p class="font-bold">@money($shift['totals']['expected_bank_other'])</p></div>
                <div><span class="text-xs text-indigo-800/70">Open credit</span><p class="font-bold">@money($shift['totals']['expected_credit'])</p></div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <x-button variant="primary" size="lg" type="submit">Balance All Waiters</x-button>
            @can('submit-reconciliation')
                <x-button variant="secondary" href="{{ tenant_route('tenant.reconciliation.create', ['date' => $date->toDateString()]) }}">
                    Continue to Close Shift →
                </x-button>
            @endcan
        </div>
    </form>
@endif
@endsection
