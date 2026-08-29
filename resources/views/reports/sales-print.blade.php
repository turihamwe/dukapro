@extends('layouts.print')

@section('title', 'Sales Report — ' . $label)

@section('content')
<div class="mb-8 border-b border-gray-200 pb-6">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">DukaPro Sales Report</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ auth()->user()->business->name ?? 'Store' }}</h1>
    <p class="mt-1 text-sm text-gray-600">{{ $label }}</p>
    <p class="mt-1 text-xs text-gray-500">Generated {{ now()->format('M j, Y g:i A') }}</p>
</div>

<div class="mb-8 grid gap-4 grid-cols-2 lg:grid-cols-3">
    <div class="rounded-xl border border-gray-200 p-4"><p class="text-xs uppercase text-gray-500">Total sales</p><p class="mt-1 text-lg font-bold">@money($totals['sales_total'])</p></div>
    <div class="rounded-xl border border-gray-200 p-4"><p class="text-xs uppercase text-gray-500">Transactions</p><p class="mt-1 text-lg font-bold">{{ number_format($totals['sales_count']) }}</p></div>
    <div class="rounded-xl border border-gray-200 p-4"><p class="text-xs uppercase text-gray-500">Cash</p><p class="mt-1 text-lg font-bold">@money($totals['cash'])</p></div>
    <div class="rounded-xl border border-gray-200 p-4"><p class="text-xs uppercase text-gray-500">Mobile money</p><p class="mt-1 text-lg font-bold">@money($totals['mobile_money'])</p></div>
    <div class="rounded-xl border border-gray-200 p-4"><p class="text-xs uppercase text-gray-500">Bank</p><p class="mt-1 text-lg font-bold">@money($totals['bank'])</p></div>
    <div class="rounded-xl border border-gray-200 p-4"><p class="text-xs uppercase text-gray-500">Credit</p><p class="mt-1 text-lg font-bold">@money($totals['credit'])</p></div>
</div>

<table class="min-w-full divide-y divide-gray-200 text-sm">
    <thead>
        <tr class="bg-gray-50">
            <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
            <th class="px-4 py-2 text-right font-medium text-gray-500">Transactions</th>
            <th class="px-4 py-2 text-right font-medium text-gray-500">Total</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse($dailyBreakdown as $row)
            <tr>
                <td class="px-4 py-2">{{ \Carbon\Carbon::parse($row->sale_date)->format('M j, Y') }}</td>
                <td class="px-4 py-2 text-right">{{ number_format($row->count) }}</td>
                <td class="px-4 py-2 text-right font-medium">@money($row->total)</td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No sales for this period.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
