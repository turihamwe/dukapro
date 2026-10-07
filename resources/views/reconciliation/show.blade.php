@extends(auth()->user()->usesCashierExperience() ? 'layouts.cashier' : 'layouts.admin')

@section('title', 'EOD Report — ' . $reconciliation->reconciliation_date->format('M j, Y'))

@section('content')
@php
    use App\Support\ReconciliationVariance;
@endphp

<x-page-header
    title="End-of-Day Report"
    subtitle="{{ $reconciliation->reconciliation_date->format('l, M j, Y') }} · {{ $reconciliation->user->name }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.index') }}">All reports</x-button>
        @can('manage-reconciliation-reports')
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.reconciliation.edit', ['reconciliation' => $reconciliation]) }}">Edit report</x-button>
            <x-delete-confirm-button
                :action="tenant_route('tenant.reconciliation.destroy', ['reconciliation' => $reconciliation])"
                message="Remove this end-of-day report?"
                detail="Sales and POS history stay unchanged. Pending shortages from this report will be cleared."
                label="Delete report"
                button-class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50"
            />
        @endcan
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.print', ['reconciliation' => $reconciliation]) }}" target="_blank">Print / PDF</x-button>
        @if($whatsAppUrl)
            <x-button variant="primary" size="sm" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">Share on WhatsApp</x-button>
        @endif
    </x-slot>
</x-page-header>

@include('reconciliation.partials.daily-trading-summary', [
    'tradingReport' => $tradingReport,
    'business' => $business,
    'showDatePicker' => auth()->user()->can('view-all-reconciliations'),
    'datePickerAction' => tenant_route('tenant.reconciliation.daily'),
])

@include('reconciliation.partials.report-body', [
    'reconciliation' => $reconciliation,
    'report' => $report,
    'business' => $business,
    'shortages' => $shortages ?? collect(),
    'hideExecutiveSummary' => filled($reconciliation->executive_summary),
])

@if(!$whatsAppUrl && $bossPhone === null)
    <x-card class="mt-4">
        <p class="text-sm text-gray-600">Add a business phone number in <a href="{{ tenant_route('tenant.business.edit') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Business settings</a> to enable WhatsApp sharing with the owner.</p>
    </x-card>
@endif
@endsection
