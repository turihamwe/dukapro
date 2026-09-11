@extends('layouts.print')

@section('title', 'Sales Report — ' . $label)

@section('content')
<div class="mb-8 border-b border-gray-200 pb-6">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ platform_brand('name') }} Daily Sales Report</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ auth()->user()->business->name ?? 'Store' }}</h1>
    <p class="mt-1 text-sm text-gray-600">{{ $label }}</p>
    <p class="mt-1 text-xs text-gray-500">Generated {{ now()->format('M j, Y g:i A') }}</p>
</div>

@include('reports.partials.sales-day-detail')
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
