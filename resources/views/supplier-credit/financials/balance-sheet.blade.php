@extends('layouts.admin')

@section('title', 'Balance Sheet')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Balance sheet" subtitle="Position as of {{ $end->format('M j, Y') }}">
    <x-slot name="actions">
        <x-document-share-toolbar
            layout="inline"
            print-label="Print"
            :whats-app-href="$shareWhatsAppUrl"
            :email-href="$shareEmailUrl"
        />
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
    $assets = $sheet['assets'];
    $cashSource = $assets['cash_source'] ?? 'eod';
    $walletLines = $assets['wallets'] ?? [];
    $cash = $assets['cash_detail'] ?? null;
@endphp

<div id="financial-printable" class="print:text-sm">
    <x-card class="mb-6">
        <p class="text-xs text-gray-500">{{ $business->name }} · As of end of {{ $label }} ({{ $end->format('M j, Y') }})</p>

        <div class="mt-6 grid gap-8 sm:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Assets</h2>

                <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Current assets</h3>
                <dl class="mt-2 divide-y divide-gray-100">
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-gray-700">Inventory (at cost)</dt>
                        <dd class="font-medium tabular-nums">{{ format_money($assets['inventory'], $business) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-gray-700">Accounts receivable</dt>
                        <dd class="font-medium tabular-nums">{{ format_money($assets['accounts_receivable'], $business) }}</dd>
                    </div>
                    <div class="py-2">
                        <div class="flex justify-between gap-4">
                            <dt class="font-medium text-gray-800">Cash &amp; cash equivalents</dt>
                            <dd class="font-semibold tabular-nums">{{ format_money($assets['cash_and_equivalents'], $business) }}</dd>
                        </div>

                        @if($cashSource === 'wallets')
                            @if(count($walletLines) > 0)
                                <ul class="mt-2 space-y-1 border-l-2 border-emerald-100 pl-3 text-xs text-gray-600">
                                    @foreach($walletLines as $walletLine)
                                        <li class="flex justify-between gap-3">
                                            <span>
                                                {{ $walletLine['name'] }}
                                                <span class="text-gray-400">· {{ $walletLine['type_label'] }}</span>
                                            </span>
                                            <span class="shrink-0 tabular-nums font-medium text-gray-800">{{ format_money($walletLine['balance'], $business) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-2 text-xs text-gray-500">Balances from active payment wallets (cash at hand, mobile money, bank).</p>
                            @else
                                <p class="mt-1 text-xs text-amber-700">Wallets are enabled but no active accounts yet — add wallets under Wallets in the menu.</p>
                            @endif
                        @elseif($cash && ($cash['date'] ?? null))
                            <p class="mt-1 text-xs text-gray-500">
                                From {{ $cash['report_count'] }} EOD report(s) on {{ $cash['date']->format('M j, Y') }}:
                                cash {{ format_money($cash['cash'], $business) }},
                                mobile {{ format_money($cash['mobile_money'], $business) }},
                                bank/other {{ format_money($cash['bank_other'], $business) }}.
                            </p>
                        @else
                            <p class="mt-1 text-xs text-amber-700">No end-of-day cash count on or before this date — enable wallets or record EOD to improve this line.</p>
                        @endif
                    </div>
                </dl>
                <div class="mt-2 flex justify-between gap-4 border-t border-gray-200 pt-3 text-sm">
                    <span class="font-medium text-gray-700">Total current assets</span>
                    <span class="font-semibold tabular-nums">{{ format_money($assets['current_assets'] ?? $assets['total'], $business) }}</span>
                </div>
                <div class="mt-1 flex justify-between gap-4 pt-2 font-semibold">
                    <span>Total assets</span>
                    <span class="tabular-nums">{{ format_money($assets['total'], $business) }}</span>
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
</div>
@endsection
