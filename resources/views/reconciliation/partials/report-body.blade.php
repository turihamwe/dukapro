@php
    $business = $reconciliation->business;
@endphp

<div class="mb-8 border-b border-gray-200 pb-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ platform_brand('name') }} EOD Report</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $business->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $reconciliation->reconciliation_date->format('l, F j, Y') }} · Cashier: {{ $reconciliation->user->name }}</p>
        </div>
        <p class="text-xs text-gray-500">Generated {{ now()->format('M j, Y g:i A') }}</p>
    </div>
</div>

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 p-4">
        <p class="text-xs font-medium uppercase text-gray-500">Total sales</p>
        <p class="mt-1 text-xl font-bold text-gray-900">@money($report['total_sales'])</p>
    </div>
    <div class="rounded-xl border border-red-100 bg-red-50 p-4">
        <p class="text-xs font-medium uppercase text-red-700">Expenses</p>
        <p class="mt-1 text-xl font-bold text-red-700">@money($report['total_expenses'])</p>
    </div>
    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
        <p class="text-xs font-medium uppercase text-amber-800">Damages</p>
        <p class="mt-1 text-xl font-bold text-amber-900">@money($report['total_damages'])</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-medium uppercase text-emerald-800">Net income</p>
        <p class="mt-1 text-xl font-bold {{ $report['net_income'] >= 0 ? 'text-emerald-900' : 'text-red-700' }}">@money($report['net_income'])</p>
        <p class="mt-1 text-[11px] text-emerald-800/70">Sales − expenses − damages</p>
    </div>
</div>

<div class="mb-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900">Cash drawer reconciliation</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Expected cash</dt><dd class="font-medium">@money($reconciliation->expected_cash)</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Actual cash</dt><dd class="font-medium">@money($reconciliation->actual_cash)</dd></div>
            <div class="flex justify-between border-t border-gray-100 pt-3"><dt class="text-gray-700">Cash variance</dt><dd class="font-semibold {{ $reconciliation->cash_variance >= 0 ? 'text-emerald-600' : 'text-red-600' }}">@money($reconciliation->cash_variance)</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Expected mobile money</dt><dd class="font-medium">@money($reconciliation->expected_mobile_money)</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Actual mobile money</dt><dd class="font-medium">@money($reconciliation->actual_mobile_money)</dd></div>
            <div class="flex justify-between border-t border-gray-100 pt-3"><dt class="text-gray-700">Mobile variance</dt><dd class="font-semibold {{ $reconciliation->mobile_variance >= 0 ? 'text-emerald-600' : 'text-red-600' }}">@money($reconciliation->mobile_variance)</dd></div>
        </dl>
    </div>

    <div class="rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900">Notes</h2>
        <p class="mt-3 text-sm text-gray-600">{{ $reconciliation->notes ?: 'No notes provided.' }}</p>
    </div>
</div>

<div class="mb-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900">Expenses ({{ $report['expenses']->count() }})</h2>
        @if($report['expenses']->isNotEmpty())
            <ul class="mt-4 divide-y divide-gray-100">
                @foreach($report['expenses'] as $expense)
                    <li class="flex items-start justify-between gap-3 py-2 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $expense->title }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($expense->category) }} · {{ optional($expense->user)->name ?? 'Staff' }}</p>
                        </div>
                        <span class="shrink-0 font-medium text-red-600">@money($expense->amount)</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm text-gray-500">No expenses recorded for this date.</p>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900">Damages &amp; write-offs ({{ $report['damages']['entry_count'] ?? 0 }})</h2>
        @if(($report['damages']['entries'] ?? collect())->isNotEmpty())
            <ul class="mt-4 divide-y divide-gray-100">
                @foreach($report['damages']['entries'] as $damage)
                    <li class="flex items-start justify-between gap-3 py-2 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ $damage->product->name }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($damage->reason) }} · {{ $damage->quantity }} {{ $damage->product->measurement_unit }} · {{ $damage->user->name }}</p>
                        </div>
                        <span class="shrink-0 font-medium text-red-600">@money($damage->lossValue())</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm text-gray-500">No damages recorded for this date.</p>
        @endif
    </div>
</div>
