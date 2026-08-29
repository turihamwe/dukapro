@extends('layouts.cashier')

@section('title', 'Close Shift')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="End-of-Day Reconciliation" subtitle="Compare system totals against your physical cash drawer and mobile money account." />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.reconciliation.store') }}" class="space-y-6">
        @csrf

        <x-input type="date" name="reconciliation_date" label="Date" value="{{ $date }}" required />

        <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
            <h2 class="text-sm font-semibold text-gray-900">Expected (from completed sales)</h2>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Cash</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['expected_cash'])</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Mobile Money</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['expected_mobile_money'])</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">{{ $expected['sale_count'] }} sales recorded</p>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <h2 class="text-sm font-semibold text-emerald-900">Daily Balancing Summary</h2>
            <p class="mt-1 text-xs text-emerald-800/80">Business-wide totals for this date</p>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs text-emerald-800/70">Total sales</p>
                    <p class="text-lg font-semibold text-emerald-900">@money($expected['total_sales'])</p>
                </div>
                <div>
                    <p class="text-xs text-emerald-800/70">Total expenses</p>
                    <p class="text-lg font-semibold text-red-700">@money($expected['total_expenses'])</p>
                </div>
                <div>
                    <p class="text-xs text-emerald-800/70">Damages (cost)</p>
                    <p class="text-lg font-semibold text-amber-800">@money($expected['total_damages'])</p>
                </div>
                <div>
                    <p class="text-xs text-emerald-800/70">Net income</p>
                    <p class="text-lg font-semibold {{ $expected['net_income'] >= 0 ? 'text-emerald-900' : 'text-red-700' }}">@money($expected['net_income'])</p>
                    <p class="text-[10px] text-emerald-800/60">Sales − expenses − damages</p>
                </div>
            </div>

            @if($expected['expenses']->isNotEmpty())
                <div class="mt-4 divide-y divide-emerald-200/60">
                    @foreach($expected['expenses'] as $expense)
                        <div class="flex items-start justify-between gap-3 py-2 text-sm">
                            <div>
                                <p class="font-medium text-emerald-950">{{ $expense->title }}</p>
                                <p class="text-xs text-emerald-800/70">{{ ucfirst($expense->category) }} · {{ optional($expense->user)->name ?? 'Staff' }}</p>
                            </div>
                            <p class="shrink-0 font-medium text-red-700">@money($expense->amount)</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-xs text-emerald-800/70">No expenses recorded for this date.</p>
            @endif

            @can('create', App\Models\Expense::class)
                <a href="{{ tenant_route('tenant.expenses.create') }}" class="mt-3 inline-block text-xs font-medium text-emerald-900 underline">Record an expense →</a>
            @endcan
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="text-sm font-semibold text-amber-900">Damages &amp; Stock Write-offs</h2>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-amber-800/70">Items written off</p>
                    <p class="text-lg font-semibold text-amber-900">{{ $expected['damages']['total_items'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-amber-800/70">Financial loss (cost)</p>
                    <p class="text-lg font-semibold text-red-700">@money($expected['damages']['total_loss'])</p>
                </div>
            </div>

            @if($expected['damages']['entries']->isNotEmpty())
                <div class="mt-4 divide-y divide-amber-200/60">
                    @foreach($expected['damages']['entries'] as $damage)
                        <div class="flex items-start justify-between gap-3 py-2 text-sm">
                            <div>
                                <p class="font-medium text-amber-950">{{ $damage->product->name }}</p>
                                <p class="text-xs text-amber-800/70">
                                    {{ ucfirst($damage->reason) }} · {{ $damage->quantity }} {{ $damage->product->measurement_unit }}
                                    · {{ $damage->user->name }}
                                </p>
                            </div>
                            <p class="shrink-0 font-medium text-red-700">@money($damage->lossValue())</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-xs text-amber-800/70">No damages recorded for this date.</p>
            @endif

            @can('log-damages')
                <a href="{{ tenant_route('tenant.damages.index', ['date' => $date]) }}" class="mt-3 inline-block text-xs font-medium text-amber-900 underline">Log or view damages →</a>
            @endcan
        </div>

        <x-input type="number" step="0.01" name="actual_cash" label="Actual Cash in Drawer" placeholder="Count physical cash" required large />
        <x-input type="number" step="0.01" name="actual_mobile_money" label="Actual Mobile Money Balance" placeholder="M-Pesa / till balance" required large />
        <x-textarea name="notes" label="Notes" rows="2" placeholder="Explain any variance..."></x-textarea>

        <x-button variant="primary" size="lg" type="submit">Submit Reconciliation</x-button>
    </form>
</x-card>

<div class="mt-4">
    @can('view-reconciliation-history')
        <x-button variant="secondary" href="{{ tenant_route('tenant.reconciliation.index') }}">View my shift history</x-button>
    @endcan
</div>
@endsection
