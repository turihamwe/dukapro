@extends('layouts.admin')

@section('title', 'Sales Reports')

@section('content')
<x-page-header title="Sales Reports" subtitle="{{ $label }}" />

<div class="mb-6 flex flex-wrap gap-2">
    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $labelOption)
        <a href="{{ tenant_route('tenant.reports.sales.index', ['period' => $key]) }}"
           class="rounded-full border px-4 py-1.5 text-sm font-medium transition {{ $period === $key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300' }}">
            {{ $labelOption }}
        </a>
    @endforeach
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total Sales" :value="format_money($totals['sales_total'])" accent="emerald" />
    <x-stat-card label="Cash" :value="format_money($totals['cash'])" accent="indigo" />
    <x-stat-card label="Mobile Money" :value="format_money($totals['mobile_money'])" accent="amber" />
    <x-stat-card label="Credit Sales" :value="format_money($totals['credit'])" accent="indigo" />
</div>

<x-card class="mt-6" :padding="false">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Transactions</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($dailyBreakdown as $row)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ \Carbon\Carbon::parse($row->sale_date)->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-600">{{ number_format($row->count) }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">@money($row->total)</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-500">No sales recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
