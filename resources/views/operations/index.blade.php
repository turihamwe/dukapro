@extends('layouts.cashier')

@section('title', 'Operations')
@section('cashier_shell_class', 'cashier-shell--operations-hub')

@section('content')
@include('layouts.partials.low-stock-alert', ['lowStockItems' => $lowStockItems ?? collect()])

<x-page-header title="Operations" subtitle="Stock, expenses, and damages" class="!mb-4 lg:!mb-3" />

<div x-data="pwaInstall()" x-cloak data-install-fallback="{{ tenant_route('tenant.downloads.index') }}"
     class="mb-4 flex flex-col gap-3 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <p class="text-sm font-medium text-indigo-950">Till app setup</p>
        <p class="text-xs text-indigo-900/80" x-show="!isStandalone && !isInstalledOnDevice">One click installs {{ platform_brand('name') }} on this device.</p>
        <p class="text-xs font-medium text-emerald-800" x-show="isStandalone || isInstalledOnDevice">✓ App installed on this device</p>
        <p x-show="installMessage && !isStandalone" x-text="installMessage" class="mt-1 text-xs text-indigo-800"></p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <button type="button"
                x-show="!isStandalone && !isInstalledOnDevice"
                @click="installApp()"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 min-h-[44px]">
            <img src="{{ asset('assets/dukapro-logo.png') }}" alt="" width="22" height="22" class="h-[22px] w-[22px] shrink-0 rounded bg-white object-contain p-0.5" aria-hidden="true">
            Install app
        </button>
        <a href="{{ tenant_route('tenant.downloads.index') }}"
           x-show="!isStandalone && !isInstalledOnDevice"
           class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
            Help
        </a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @can('view-inventory')
        <a href="{{ tenant_route('tenant.inventory.index') }}"
           class="group flex min-h-[120px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:border-emerald-300 hover:shadow-md">
            <span class="mb-2 text-3xl leading-none">📦</span>
            <span class="text-base font-semibold text-gray-900 group-hover:text-emerald-700">Stock</span>
            <span class="mt-1 text-xs text-gray-500">View inventory levels</span>
        </a>
    @endcan

    @can('top-up-inventory')
        <a href="{{ tenant_route('tenant.inventory.top-up') }}"
           class="group flex min-h-[120px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:border-emerald-300 hover:shadow-md">
            <span class="mb-2 text-3xl leading-none">➕</span>
            <span class="text-base font-semibold text-gray-900 group-hover:text-emerald-700">Top-up</span>
            <span class="mt-1 text-xs text-gray-500">Restock existing products</span>
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

    @can('view-sales-documents')
        <a href="{{ tenant_route('tenant.sales.documents') }}"
           class="group flex min-h-[120px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm transition hover:border-indigo-300 hover:shadow-md">
            <span class="mb-2 text-3xl leading-none">🧾</span>
            <span class="text-base font-semibold text-gray-900 group-hover:text-indigo-700">Invoices &amp; receipts</span>
            <span class="mt-1 text-xs text-gray-500">Search and reprint sales documents</span>
        </a>
    @endcan
</div>
@endsection
