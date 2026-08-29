@extends('layouts.admin')

@section('title', 'EOD Report — ' . $reconciliation->reconciliation_date->format('M j, Y'))

@section('content')
@php
    $business = $reconciliation->business;
@endphp

<x-page-header
    title="End-of-Day Report"
    subtitle="{{ $reconciliation->reconciliation_date->format('l, M j, Y') }} · {{ $reconciliation->user->name }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.index') }}">All reports</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reconciliation.print', ['reconciliation' => $reconciliation]) }}" target="_blank">Print / PDF</x-button>
        @if($whatsAppUrl)
            <x-button variant="primary" size="sm" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">Share on WhatsApp</x-button>
        @endif
    </x-slot>
</x-page-header>

@include('reconciliation.partials.report-body', ['reconciliation' => $reconciliation, 'report' => $report, 'business' => $business])

@if(!$whatsAppUrl && $bossPhone === null)
    <x-card class="mt-4">
        <p class="text-sm text-gray-600">Add a business phone number in <a href="{{ tenant_route('tenant.business.edit') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Business settings</a> to enable WhatsApp sharing with the owner.</p>
    </x-card>
@endif
@endsection
