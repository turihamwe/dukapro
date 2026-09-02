@extends('layouts.cashier')

@section('title', 'Operations')
@section('cashier_shell_class', 'cashier-shell--operations-hub')

@section('content')
<x-page-header title="Operations" subtitle="Stock, expenses, and damages" class="!mb-4 lg:!mb-3" />

<div class="grid gap-4 sm:grid-cols-3">
    @can('view-inventory')
        <a href="{{ tenant_route('tenant.inventory.index') }}"
           class="group flex min-h-[120px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:border-emerald-300 hover:shadow-md">
            <span class="mb-2 text-3xl leading-none">📦</span>
            <span class="text-base font-semibold text-gray-900 group-hover:text-emerald-700">Stock</span>
            <span class="mt-1 text-xs text-gray-500">View inventory levels</span>
        </a>
    @endcan

    @can('record-expenses')
        <a href="{{ tenant_route('tenant.expenses.create') }}"
           class="group flex min-h-[120px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:border-emerald-300 hover:shadow-md">
            <span class="mb-2 text-3xl leading-none">📝</span>
            <span class="text-base font-semibold text-gray-900 group-hover:text-emerald-700">Expense</span>
            <span class="mt-1 text-xs text-gray-500">Record operating costs</span>
        </a>
    @endcan

    @can('log-damages')
        <a href="{{ tenant_route('tenant.damages.index') }}"
           class="group flex min-h-[120px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:border-emerald-300 hover:shadow-md">
            <span class="mb-2 text-3xl leading-none">💥</span>
            <span class="text-base font-semibold text-gray-900 group-hover:text-emerald-700">Damage</span>
            <span class="mt-1 text-xs text-gray-500">Log write-offs &amp; loss</span>
        </a>
    @endcan
</div>
@endsection
