@extends('layouts.admin')

@section('title', 'Sales Report — ' . $label)

@section('content')
<x-page-header title="Daily Sales Report" :subtitle="$label">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reports.sales.index', ['period' => $period]) }}">
            Back to summary
        </x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.reports.sales.day.print', ['date' => $date, 'period' => $period]) }}" target="_blank">
            Print / PDF
        </x-button>
    </x-slot>
</x-page-header>

@include('reports.partials.sales-day-detail')
@endsection
