@extends('layouts.app')

@section('title', 'Submit Reconciliation')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="End-of-Day Reconciliation" subtitle="Compare system totals against your physical cash drawer and mobile money account." />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.reconciliation.store') }}" class="space-y-6">
        @csrf

        <x-input type="date" name="reconciliation_date" label="Date" value="{{ $date }}" required />

        <div class="rounded-xl border border-gray-100 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-800/50">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Expected (from completed sales)</h2>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cash</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($expected['expected_cash'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Mobile Money</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($expected['expected_mobile_money'], 2) }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">{{ $expected['sale_count'] }} sales recorded</p>
        </div>

        <x-input type="number" step="0.01" name="actual_cash" label="Actual Cash in Drawer" placeholder="Count physical cash" required large />
        <x-input type="number" step="0.01" name="actual_mobile_money" label="Actual Mobile Money Balance" placeholder="M-Pesa / till balance" required large />
        <x-textarea name="notes" label="Notes" rows="2" placeholder="Explain any variance..."></x-textarea>

        <x-button variant="primary" size="lg" type="submit">Submit Reconciliation</x-button>
    </form>
</x-card>
@endsection
