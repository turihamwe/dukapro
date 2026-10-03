@extends('layouts.admin')

@section('title', 'Balance Sheet')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Balance sheet" subtitle="Position as of {{ $end->format('M j, Y') }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" type="button" onclick="window.print()">Print</x-button>
    </x-slot>
</x-page-header>

@include('supplier-credit.financials._nav-links')

@include('supplier-credit.financials._period-filter', [
    'period' => $period,
    'routeName' => 'tenant.supplier-credit.financials.balance-sheet',
    'start' => $start,
    'end' => $end,
])

@php
    $cash = $sheet['assets']['cash_detail'];
@endphp

<div id="financial-printable" class="print:text-sm">
    <x-card class="mb-6">
        <p class="text-xs text-gray-500">{{ $business->name }} · As of end of {{ $label }} ({{ $end->format('M j, Y') }})</p>

        <div class="mt-6 grid gap-8 sm:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Assets</h2>
                <dl class="mt-3 divide-y divide-gray-100">
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-gray-700">Inventory (at cost)</dt>
                        <dd class="font-medium tabular-nums">{{ format_money($sheet['assets']['inventory'], $business) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-gray-700">Accounts receivable</dt>
                        <dd class="font-medium tabular-nums">{{ format_money($sheet['assets']['accounts_receivable'], $business) }}</dd>
                    </div>
                    <div class="py-2">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-700">Cash &amp; equivalents</dt>
                            <dd class="font-medium tabular-nums">{{ format_money($sheet['assets']['cash_and_equivalents'], $business) }}</dd>
                        </div>
                        @if($cash['date'])
                            <p class="mt-1 text-xs text-gray-500">
                                From {{ $cash['report_count'] }} EOD report(s) on {{ $cash['date']->format('M j, Y') }}:
                                cash {{ format_money($cash['cash'], $business) }},
                                mobile {{ format_money($cash['mobile_money'], $business) }},
                                bank/other {{ format_money($cash['bank_other'], $business) }}.
                            </p>
                        @else
                            <p class="mt-1 text-xs text-amber-700">No end-of-day cash count on or before this date — record EOD to improve this line.</p>
                        @endif
                    </div>
                </dl>
                <div class="mt-2 flex justify-between gap-4 border-t border-gray-200 pt-3 font-semibold">
                    <span>Total assets</span>
                    <span class="tabular-nums">{{ format_money($sheet['assets']['total'], $business) }}</span>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Liabilities</h2>
                <dl class="mt-3 divide-y divide-gray-100">
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-gray-700">Accounts payable (supplier credit)</dt>
                        <dd class="font-medium tabular-nums">{{ format_money($sheet['liabilities']['accounts_payable'], $business) }}</dd>
                    </div>
                </dl>
                <div class="mt-2 flex justify-between gap-4 border-t border-gray-200 pt-3 font-semibold">
                    <span>Total liabilities</span>
                    <span class="tabular-nums">{{ format_money($sheet['liabilities']['total'], $business) }}</span>
                </div>
                <p class="mt-3 text-xs text-gray-500">
                    <a href="{{ tenant_route('tenant.supplier-credit.bills.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800">View open bills</a>
                </p>
            </div>
        </div>

        <div class="mt-8 flex justify-between gap-4 rounded-lg bg-emerald-50 px-4 py-3 font-semibold">
            <span class="text-emerald-900">Net position (assets − liabilities)</span>
            <span class="tabular-nums text-emerald-900">{{ format_money($sheet['net_position'], $business) }}</span>
        </div>
    </x-card>

    <x-card class="!p-4 bg-gray-50 border-dashed">
        <p class="text-xs text-gray-600">
            <strong>Audit note:</strong> Inventory and receivable balances reflect current ledger values in DukaPro (not a historical snapshot unless you run this as of today).
            Payables are outstanding supplier credit bills. This is a simplified balance sheet for owner review — not a statutory filing.
            Pair with the
            <a href="{{ tenant_route('tenant.supplier-credit.financials.income-statement', request()->only(['period', 'from', 'to'])) }}" class="font-medium text-indigo-600">income statement</a>
            and
            <a href="{{ tenant_route('tenant.reports.sales.index') }}" class="font-medium text-indigo-600">sales reports</a>
            for a fuller picture.
        </p>
    </x-card>
</div>
@endsection
