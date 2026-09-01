@extends('layouts.cashier')

@section('title', 'Close Shift')
@section('container_class', 'max-w-2xl')

@section('content')
<x-page-header title="Close Shift" subtitle="Count your drawer and submit end-of-day balancing." />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.reconciliation.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="reconciliation_date" value="{{ $date }}">

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your sales today</p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-gray-500">Expected cash</p>
                    <p class="text-lg font-bold text-gray-900">@money($expected['expected_cash'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Expected mobile</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['expected_mobile_money'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Bank &amp; other</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['expected_bank_other'] ?? 0)</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Sales count</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $expected['sale_count'] }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 text-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Business totals today</p>
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div>
                    <p class="text-xs text-gray-500">Expenses</p>
                    <p class="font-semibold text-red-600">@money($expected['total_expenses'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Damages</p>
                    <p class="font-semibold text-amber-700">@money($expected['total_damages'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Net income</p>
                    <p class="font-semibold {{ $expected['net_income'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">@money($expected['net_income'])</p>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 text-xs">
                @can('create', App\Models\Expense::class)
                    <a href="{{ tenant_route('tenant.expenses.create') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Record expense →</a>
                @endcan
                @can('log-damages')
                    <a href="{{ tenant_route('tenant.damages.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Log damage →</a>
                @endcan
            </div>
        </div>

        <x-input type="number" step="0.01" name="actual_cash" label="Actual cash in drawer" placeholder="Count physical cash" required large />
        <x-input type="number" step="0.01" name="actual_mobile_money" label="Actual mobile money balance" placeholder="M-Pesa / till balance" required large />
        <x-input type="number" step="0.01" name="actual_bank_other" label="Bank &amp; other methods received" value="{{ old('actual_bank_other', 0) }}" large />
        <x-textarea name="notes" label="Notes" rows="2" placeholder="Explain any missing money...">{{ old('notes') }}</x-textarea>

        <p class="text-xs text-gray-500">
            Balancing: Expected cash = Actual cash + Mobile money + Bank &amp; other + Expenses + Damages + Missing money
        </p>

        <x-button variant="primary" size="lg" type="submit">Submit Reconciliation</x-button>
    </form>
</x-card>

@can('view-reconciliation-history')
    <div class="mt-4">
        <x-button variant="secondary" href="{{ tenant_route('tenant.reconciliation.index') }}">View shift history</x-button>
    </div>
@endcan
@endsection
