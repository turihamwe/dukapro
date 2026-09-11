@extends('layouts.admin')

@section('title', 'Sales Reports')

@section('content')
<x-page-header title="Sales Reports" subtitle="{{ $label }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reports.sales.print', ['period' => $period]) }}" target="_blank">
            Print summary
        </x-button>
    </x-slot>
</x-page-header>

<x-report-period-tabs :period="$period" route-name="tenant.reports.sales.index" />

<div id="sales-report-printable">
    <div class="grid gap-4 grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-stat-card label="Total Sales" :value="format_money($totals['sales_total'])" accent="emerald" />
        <x-stat-card label="Transactions" :value="number_format($totals['sales_count'])" accent="indigo" />
        <x-stat-card label="Cash" :value="format_money($totals['cash'])" accent="indigo" />
        <x-stat-card label="Mobile Money" :value="format_money($totals['mobile_money'])" accent="amber" />
        <x-stat-card label="Bank" :value="format_money($totals['bank'])" accent="sky" />
    </div>

    <x-card class="mt-6" :padding="false">
        <div class="border-b border-gray-100 px-4 py-3 sm:px-6">
            <h2 class="text-sm font-semibold text-gray-900">Sales by date</h2>
            <p class="mt-1 text-xs text-gray-500">Select a date to view products sold, cashiers, and transaction details for that day.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Transactions</th>
                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Report</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($dailyBreakdown as $row)
                        @php $saleDate = \Carbon\Carbon::parse($row->sale_date)->toDateString(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($row->sale_date)->format('l, M j, Y') }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right text-sm text-gray-600">{{ number_format($row->count) }}</td>
                            <td class="px-4 sm:px-6 py-4 text-right text-sm font-semibold text-emerald-700">@money($row->total)</td>
                            <td class="px-4 sm:px-6 py-4 text-right">
                                <a href="{{ tenant_route('tenant.reports.sales.show', ['date' => $saleDate, 'period' => $period]) }}"
                                   class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                    View report
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No sales recorded for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection

@push('styles')
<style>
@media print {
    .modern-sidebar, header, footer, .no-print, nav, aside { display: none !important; }
    main { padding: 0 !important; }
}
</style>
@endpush
