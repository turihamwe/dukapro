@extends('layouts.admin')

@section('title', 'Transactions Log')

@section('content')
@php
    $filterQuery = array_filter([
        'start_date' => $startDate,
        'end_date' => $endDate,
        'branch_id' => ($branch['show_picker'] ?? false) ? $branch['selected'] : null,
    ], function ($value) {
        return $value !== null && $value !== '';
    });
@endphp

<x-page-header title="Transactions Log" subtitle="Sales, bills, stock movements, and wallet activity from the records you already keep">
</x-page-header>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach($types as $key => $label)
        <a href="{{ tenant_route('tenant.transactions-log.index', array_merge($filterQuery, ['type' => $key])) }}"
           class="rounded-full border px-3 py-1 text-xs font-medium {{ $type === $key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<x-card class="mb-4">
    <form method="get" action="{{ tenant_route('tenant.transactions-log.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <input type="hidden" name="type" value="{{ $type }}">
        <div>
            <label for="start_date" class="mb-1 block text-xs font-medium text-gray-700">Start date</label>
            <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="end_date" class="mb-1 block text-xs font-medium text-gray-700">End date</label>
            <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        @if($branch['show_picker'] ?? false)
            <div>
                <label for="branch_id" class="mb-1 block text-xs font-medium text-gray-700">Branch</label>
                <select id="branch_id" name="branch_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="all" @selected($branch['selected'] === 'all')>All branches</option>
                    @foreach($branch['branches'] as $branchOption)
                        <option value="{{ $branchOption->id }}" @selected((string) $branch['selected'] === (string) $branchOption->id)>{{ $branchOption->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <button type="submit" class="inline-flex min-h-[40px] items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Apply
            </button>
        </div>
    </form>
</x-card>

@if($notice)
    <x-card class="mb-4 text-sm text-gray-600">{{ $notice }}</x-card>
@endif

<x-card :padding="false">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date/Time</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Entity Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Activity / Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference #</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount / Quantity Impact</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Branch</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $row->occurred_label }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ $row->category }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $row->entity_name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $row->activity }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $row->reference_no }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium {{ (float) $row->impact_value < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ $row->impact_label }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $row->branch_name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No transactions in this date range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $rows->links() }}</div>
    @endif
</x-card>
@endsection
