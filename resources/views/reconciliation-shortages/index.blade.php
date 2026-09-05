@extends('layouts.admin')

@section('title', 'Shift Shortages')

@section('content')
<x-page-header
    title="Shift Shortages"
    subtitle="Outstanding amounts from EOD reconciliations — clear staff when they pay up."
>
    <x-slot name="actions">
        <div class="flex flex-wrap gap-2">
            <x-button variant="{{ $status === 'pending' ? 'primary' : 'secondary' }}" size="sm"
                      href="{{ tenant_route('tenant.reconciliation-shortages.index', ['status' => 'pending']) }}">
                Pending
            </x-button>
            <x-button variant="{{ $status === 'settled' ? 'primary' : 'secondary' }}" size="sm"
                      href="{{ tenant_route('tenant.reconciliation-shortages.index', ['status' => 'settled']) }}">
                Cleared
            </x-button>
            <x-button variant="{{ $status === 'all' ? 'primary' : 'secondary' }}" size="sm"
                      href="{{ tenant_route('tenant.reconciliation-shortages.index', ['status' => 'all']) }}">
                All
            </x-button>
        </div>
    </x-slot>
</x-page-header>

@if($status === 'pending' && $pendingTotal > 0)
    <x-card class="mb-4 border-amber-200 bg-amber-50">
        <p class="text-sm text-amber-950">
            <span class="font-semibold">@money($pendingTotal)</span> outstanding across pending shortages.
            Uncleared amounts can be deducted from salary at month end.
        </p>
    </x-card>
@endif

<x-card :padding="false" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Staff</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Source</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Outstanding</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($shortages as $shortage)
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $shortage->shortage_date->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $shortage->user->name ?? 'Staff' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $shortage->sourceLabel() }}
                            @if($shortage->reconciliation)
                                <a href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $shortage->reconciliation]) }}"
                                   class="mt-0.5 block text-xs text-indigo-600 hover:text-indigo-800">View EOD →</a>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">@money($shortage->amount)</td>
                        <td class="px-6 py-4 text-right text-sm font-semibold {{ $shortage->isPending() ? 'text-red-700' : 'text-emerald-700' }}">
                            @money($shortage->outstandingAmount())
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($shortage->status === 'settled')
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Cleared</span>
                                @if($shortage->settled_at)
                                    <p class="mt-1 text-xs text-gray-500">{{ $shortage->settled_at->format('M j, Y') }}</p>
                                @endif
                            @elseif($shortage->status === 'waived')
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">Waived</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @if($shortage->isPending())
                                @can('settle-reconciliation-shortages')
                                    <details class="inline-block text-left">
                                        <summary class="cursor-pointer text-xs font-medium text-indigo-600 hover:text-indigo-800">Clear payment</summary>
                                        <form method="POST" action="{{ tenant_route('tenant.reconciliation-shortages.settle', ['shortage' => $shortage]) }}" class="mt-2 w-56 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                                            @csrf
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Amount received</label>
                                            <input type="number" step="0.01" name="amount_settled" value="{{ $shortage->outstandingAmount() }}"
                                                   class="mb-2 w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm" required>
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Notes</label>
                                            <input type="text" name="settlement_notes" placeholder="Receipt / comment"
                                                   class="mb-2 w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                            <x-button variant="primary" size="sm" type="submit" class="w-full justify-center">Mark cleared</x-button>
                                        </form>
                                        <form method="POST" action="{{ tenant_route('tenant.reconciliation-shortages.waive', ['shortage' => $shortage]) }}" class="mt-2 w-56 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                                            @csrf
                                            <input type="hidden" name="settlement_notes" value="Waived by management">
                                            <x-button variant="secondary" size="sm" type="submit" class="w-full justify-center">Waive</x-button>
                                        </form>
                                    </details>
                                @endcan
                            @elseif($shortage->settlement_notes)
                                <p class="max-w-[12rem] text-xs text-gray-500">{{ $shortage->settlement_notes }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                            @if($status === 'pending')
                                No pending shortages. Staff are balanced for recorded shifts.
                            @else
                                No shortage records found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
