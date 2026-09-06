@extends('layouts.cashier')

@section('title', 'Edit Shift Report')
@section('container_class', 'max-w-2xl')
@section('main_class', 'lg:!py-2')

@section('content')
<x-page-header title="Edit Today's Shift Report" subtitle="Update your end-of-day balancing for {{ $reconciliation->reconciliation_date->format('M j, Y') }}." class="!mb-4 lg:!mb-3" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.reconciliation.update', ['reconciliation' => $reconciliation]) }}" class="space-y-4 lg:space-y-3">
        @csrf
        @method('PUT')
        <input type="hidden" name="reconciliation_date" value="{{ $date }}">

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your sales today</p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-gray-500">Expected cash</p>
                    <p class="text-lg font-bold text-gray-900">@money($expected['expected_cash'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Expected mobile</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['expected_mobile_money'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Bank &amp; other</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['expected_bank_other'] ?? 0)</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total sales</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['user_total_sales'])</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
            You can edit today's submission only. Past shift reports are locked once the day has passed.
        </div>

        <x-input type="number" step="0.01" name="actual_cash" label="Actual cash in drawer" value="{{ old('actual_cash', $reconciliation->actual_cash) }}" required large />
        <x-input type="number" step="0.01" name="actual_mobile_money" label="Actual mobile money balance" value="{{ old('actual_mobile_money', $reconciliation->actual_mobile_money) }}" large />
        <x-input type="number" step="0.01" name="actual_bank_other" label="Bank &amp; other methods received" value="{{ old('actual_bank_other', $reconciliation->actual_bank_other ?? 0) }}" large />
        <x-input type="number" step="0.01" name="extra_cash" label="Extra cash found" value="{{ old('extra_cash', $reconciliation->extra_cash ?? 0) }}" large />
        <x-textarea name="notes" label="Notes" rows="2">{{ old('notes', $reconciliation->notes) }}</x-textarea>

        @can('access-waiter-shift-balancing')
            @if(isset($waiterBalances) && $waiterBalances->isNotEmpty())
                <label class="flex items-start gap-2 rounded-xl border border-violet-200 bg-violet-50 p-4">
                    <input type="checkbox" name="bundle_waiter_balances" value="1" class="mt-0.5 rounded border-violet-300 text-violet-600" checked>
                    <span class="text-xs text-violet-950">Include balanced waiter summaries in this EOD submission</span>
                </label>
            @endif
        @endcan

        <div class="flex flex-wrap gap-3">
            <x-button variant="primary" size="lg" type="submit">Save changes</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $reconciliation]) }}">Cancel</x-button>
        </div>
    </form>
</x-card>
@endsection
