@extends('layouts.admin')

@section('title', 'Income Statement')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Income statement" subtitle="{{ $label }} — for self-audit">
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
    'routeName' => 'tenant.supplier-credit.financials.income-statement',
    'start' => $start,
    'end' => $end,
])

<div id="financial-printable" class="print:text-sm">
    <x-card class="mb-6">
        <p class="text-xs text-gray-500">{{ $business->name }} · Period {{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</p>
        <h2 class="mt-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Revenue &amp; gross profit</h2>
        <dl class="mt-3 divide-y divide-gray-100">
            <div class="flex justify-between gap-4 py-2">
                <dt class="text-gray-700">Sales revenue (completed sales)</dt>
                <dd class="font-medium tabular-nums text-gray-900">{{ format_money($statement['revenue'], $business) }}</dd>
            </div>
            <div class="flex justify-between gap-4 py-2">
                <dt class="text-gray-700">Cost of goods sold</dt>
                <dd class="font-medium tabular-nums text-red-700">({{ format_money($statement['cost_of_goods_sold'], $business) }})</dd>
            </div>
            <div class="flex justify-between gap-4 py-2 font-semibold">
                <dt class="text-gray-900">Gross profit</dt>
                <dd class="tabular-nums text-emerald-700">{{ format_money($statement['gross_profit'], $business) }}</dd>
            </div>
        </dl>

        <h2 class="mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500">Operating expenses</h2>
        @if($statement['expenses_by_category']->isEmpty())
            <p class="mt-3 text-sm text-gray-500">No expenses recorded in this period.</p>
        @else
            <dl class="mt-3 divide-y divide-gray-100">
                @foreach($statement['expenses_by_category'] as $row)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-gray-700">{{ $expenseCategoryLabels[$row['category']] ?? ucfirst(str_replace('_', ' ', $row['category'])) }}</dt>
                        <dd class="font-medium tabular-nums text-red-700">({{ format_money($row['total'], $business) }})</dd>
                    </div>
                @endforeach
            </dl>
        @endif
        <div class="mt-2 flex justify-between gap-4 border-t border-gray-200 pt-3 font-medium">
            <span class="text-gray-900">Total operating expenses</span>
            <span class="tabular-nums text-red-700">({{ format_money($statement['total_expenses'], $business) }})</span>
        </div>

        <h2 class="mt-8 text-sm font-semibold uppercase tracking-wide text-gray-500">Other</h2>
        <dl class="mt-3 divide-y divide-gray-100">
            <div class="flex justify-between gap-4 py-2">
                <dt class="text-gray-700">Stock losses (damages)</dt>
                <dd class="font-medium tabular-nums text-red-700">({{ format_money($statement['stock_losses'], $business) }})</dd>
            </div>
        </dl>

        <div class="mt-6 flex justify-between gap-4 rounded-lg bg-indigo-50 px-4 py-3 font-semibold">
            <span class="text-indigo-900">Net income</span>
            <span class="tabular-nums text-indigo-900">{{ format_money($statement['net_income'], $business) }}</span>
        </div>
        <p class="mt-2 text-xs text-gray-500">Net income = gross profit − operating expenses − stock losses. Matches the logic used on end-of-day reports (with COGS shown separately above).</p>
    </x-card>

    <x-card>
        <h2 class="text-sm font-semibold text-gray-900">Purchasing activity (same period)</h2>
        <p class="mt-1 text-xs text-gray-500">Supplier credit is tracked separately from the P&amp;L above. Use this to reconcile bills and payments with inventory.</p>
        <dl class="mt-4 divide-y divide-gray-100">
            <div class="flex justify-between gap-4 py-2">
                <dt class="text-gray-700">Stock received on supplier credit (bills)</dt>
                <dd class="font-medium tabular-nums">{{ format_money($statement['purchasing']['credit_purchases_received'], $business) }}</dd>
            </div>
            <div class="flex justify-between gap-4 py-2">
                <dt class="text-gray-700">Payments to suppliers</dt>
                <dd class="font-medium tabular-nums">{{ format_money($statement['purchasing']['supplier_payments_made'], $business) }}</dd>
            </div>
        </dl>
        <p class="mt-4 text-xs text-gray-500">
            <a href="{{ tenant_route('tenant.supplier-credit.bills.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Open bills</a>
            ·
            <a href="{{ tenant_route('tenant.supplier-credit.payments.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Payments made</a>
        </p>
    </x-card>
</div>
@endsection
