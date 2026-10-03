@extends('layouts.admin')

@section('title', 'Purchases Overview')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Purchases overview" subtitle="{{ $label }} — supplier credit activity">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">Bills</x-button>
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.supplier-credit.receives.create') }}">+ Receive stock</x-button>
    </x-slot>
</x-page-header>

@php
    $customFrom = request('from', $period === 'custom' ? $start->toDateString() : '');
    $customTo = request('to', $period === 'custom' ? $end->toDateString() : '');
    $periodPill = 'snap-start shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition min-h-[44px] inline-flex items-center';
    $periodActive = 'border-indigo-600 bg-indigo-600 text-white';
    $periodIdle = 'border-gray-200 bg-white text-gray-700 hover:border-gray-300';
@endphp
<div class="mb-6 flex flex-wrap items-center gap-2 pb-1">
    @foreach(\App\Support\ReportPeriodResolver::periods() as $key => $labelOption)
        <a href="{{ tenant_route('tenant.supplier-credit.overview.index', ['period' => $key]) }}"
           class="{{ $periodPill }} {{ $period === $key ? $periodActive : $periodIdle }}">
            {{ $labelOption }}
        </a>
    @endforeach
    <details class="relative shrink-0" @if($period === 'custom') open @endif>
        <summary class="{{ $periodPill }} {{ $period === 'custom' ? $periodActive : $periodIdle }} cursor-pointer list-none inline-flex items-center gap-1.5 [&::-webkit-details-marker]:hidden">
            Custom
            <svg class="h-3.5 w-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="absolute left-0 top-[calc(100%+0.35rem)] z-30 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
            <form method="GET" action="{{ tenant_route('tenant.supplier-credit.overview.index') }}" class="space-y-3">
                <input type="hidden" name="period" value="custom">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">From</label>
                        <input type="date" name="from" value="{{ $customFrom }}" required
                               class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">To</label>
                        <input type="date" name="to" value="{{ $customTo }}" required
                               class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>
                <x-button variant="primary" size="sm" type="submit" class="w-full">Apply</x-button>
            </form>
        </div>
    </details>
</div>

<p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">All time (current position)</p>
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Outstanding payable" :value="format_money($totals['total_payable'])" accent="amber" />
    <x-stat-card label="Open bills" :value="number_format($totals['open_bills_count'])" accent="indigo" />
    <x-stat-card label="Active vendors" :value="number_format($totals['active_vendors_count'])" accent="emerald" />
</div>

<p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">In selected period ({{ $label }})</p>
<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-stat-card label="Bills received" :value="number_format($snapshot['purchases_count'])" accent="indigo" />
    <x-stat-card label="Credit received" :value="format_money($snapshot['purchases_total'])" accent="amber" />
    <x-stat-card label="Payments recorded" :value="number_format($snapshot['payments_count'])" accent="emerald" />
    <x-stat-card label="Cash paid out" :value="format_money($snapshot['payments_total'])" accent="sky" />
</div>

<h2 class="mb-3 text-sm font-semibold text-gray-900">Activity timeline</h2>
<p class="mb-4 text-xs text-gray-500">Bills by purchase date; payments by payment date. Newest first.</p>

<div class="space-y-3">
    @forelse($timeline as $event)
        <x-card class="!p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($event['type'] === 'purchase')
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">Bill</span>
                        @else
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Payment</span>
                        @endif
                        <span class="text-sm font-medium text-gray-900">{{ $event['supplier_name'] }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $event['occurred_at']->format('M j, Y') }}
                        @if($event['type'] === 'payment')
                            · {{ $event['occurred_at']->format('g:i A') }}
                        @endif
                        · {{ $event['label'] }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-2 text-right">
                    <p class="text-sm font-semibold {{ $event['type'] === 'payment' ? 'text-emerald-700' : 'text-amber-800' }}">
                        {{ ($event['type'] === 'payment' ? '−' : '') . format_money($event['amount']) }}
                    </p>
                    @if($event['purchase'])
                        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $event['purchase']]) }}">View bill</x-button>
                    @endif
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No purchase or payment activity in this period.</x-card>
    @endforelse
</div>
@endsection
