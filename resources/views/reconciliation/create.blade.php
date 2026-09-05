@extends('layouts.cashier')

@section('title', 'Close Shift')
@section('container_class', 'max-w-2xl')
@section('main_class', 'lg:!py-2')

@section('content')
<x-page-header title="Close Shift" subtitle="Count your drawer and submit end-of-day balancing." class="!mb-4 lg:!mb-3" />

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.reconciliation.store') }}" class="space-y-4 lg:space-y-3">
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
                    <p class="text-xs text-gray-500">Total sales</p>
                    <p class="text-lg font-semibold text-gray-900">@money($expected['user_total_sales'])</p>
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
                    <a href="{{ tenant_route('tenant.operations.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Operations hub →</a>
                @endcan
            </div>
        </div>

        <x-input type="number" step="0.01" name="actual_cash" label="Actual cash in drawer" placeholder="Count physical cash" required large />
        <x-input type="number" step="0.01" name="actual_mobile_money" label="Actual mobile money balance" placeholder="M-Pesa / till balance" required large />
        <x-input type="number" step="0.01" name="actual_bank_other" label="Bank &amp; other methods received" value="{{ old('actual_bank_other', 0) }}" large />
        <x-textarea name="notes" label="Notes" rows="2" placeholder="Explain any missing money...">{{ old('notes') }}</x-textarea>

        @if($business->usesShiftWaiterMode())
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-violet-950">Waiter shift summaries</p>
                        <p class="mt-1 text-xs text-violet-900/80">Balance waiters before closing shift, then bundle summaries into this EOD report.</p>
                    </div>
                    <a href="{{ tenant_route('tenant.waiter-shift.index', ['date' => $date]) }}" class="text-xs font-medium text-violet-700 hover:text-violet-900">Open waiter balancing →</a>
                </div>

                @if(isset($waiterBalances) && $waiterBalances->isNotEmpty())
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-xs">
                            <thead>
                                <tr class="text-violet-900/70">
                                    <th class="py-1 pr-3">Waiter</th>
                                    <th class="py-1 pr-3">Expected</th>
                                    <th class="py-1 pr-3">Submitted</th>
                                    <th class="py-1">Shortage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($waiterBalances as $balance)
                                    <tr class="border-t border-violet-200/60">
                                        <td class="py-2 pr-3 font-medium">{{ $balance->waiter->name ?? 'Staff' }}</td>
                                        <td class="py-2 pr-3">@money($balance->expectedTotal())</td>
                                        <td class="py-2 pr-3">@money($balance->actualTotal())</td>
                                        <td class="py-2 font-semibold {{ $balance->shortage >= 0 ? 'text-emerald-700' : 'text-red-700' }}">@money($balance->shortage)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <label class="mt-4 flex items-start gap-2">
                        <input type="checkbox" name="bundle_waiter_balances" value="1" class="mt-0.5 rounded border-violet-300 text-violet-600" checked>
                        <span class="text-xs text-violet-950">Include balanced waiter summaries in this EOD submission</span>
                    </label>
                @elseif(isset($waiterShift) && $waiterShift['rows']->isNotEmpty())
                    <p class="mt-3 text-xs text-amber-800">Waiters have orders today but are not balanced yet. Use “Balance All Waiters” first for accurate EOD.</p>
                @endif
            </div>
        @endif

        <x-button variant="primary" size="lg" type="submit">Submit Reconciliation</x-button>
    </form>
</x-card>

@can('view-reconciliation-history')
    <div class="mt-4">
        <x-button variant="secondary" href="{{ tenant_route('tenant.reconciliation.index') }}">View shift history</x-button>
    </div>
@endcan
@endsection
