@extends('layouts.admin')

@section('title', 'Daily Trading Summary — ' . $date->format('M j, Y'))

@section('content')
<x-page-header
    title="Daily Trading Summary"
    subtitle="Review sales performance and audit trail for any trading day">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.index') }}">EOD reports</x-button>
    </x-slot>
</x-page-header>

@include('reconciliation.partials.daily-trading-summary', [
    'tradingReport' => $tradingReport,
    'business' => $business,
])

@if($reconciliations->isNotEmpty())
    <x-card :padding="false" class="overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Cashier shift reports</h3>
            <p class="mt-1 text-xs text-gray-500">Submitted end-of-day reconciliations for {{ $date->format('M j, Y') }}.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($reconciliations as $reconciliation)
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $reconciliation->user->name }}</p>
                        <p class="text-xs text-gray-500">
                            Sales @money($reconciliation->total_sales ?? 0)
                            · Net @money($reconciliation->net_income ?? 0)
                        </p>
                    </div>
                    <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.show', ['reconciliation' => $reconciliation]) }}">
                        View shift report
                    </x-button>
                </div>
            @endforeach
        </div>
    </x-card>
@endif
@endsection
