@extends('layouts.admin')

@section('title', 'Sales Report — ' . $label)

@section('content')
@php
    $branchQuery = $branchQuery ?? [];
@endphp
<x-page-header title="Daily Sales Report" :subtitle="$label . (!empty($branchName) ? ' · ' . $branchName : '')">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reports.sales.index', array_merge(['period' => $period], $branchQuery)) }}">
            Back to summary
        </x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reports.sales.day.print', array_merge(['date' => $date, 'period' => $period], $branchQuery)) }}" target="_blank">
            Print / PDF
        </x-button>
    </x-slot>
</x-page-header>

@include('reports.partials.sales-branch-filter', [
    'branchFormAction' => tenant_route('tenant.reports.sales.show', ['date' => $date, 'period' => $period]),
])

@include('reports.partials.sales-day-detail')
@endsection
