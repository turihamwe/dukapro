@php
    $business = $reconciliation->business;
    $missingMoney = $reconciliation->missing_money ?? 0;
@endphp

<div class="mb-6 border-b border-gray-200 pb-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ platform_brand('name') }} EOD Report</p>
    <h1 class="mt-1 text-xl font-bold text-gray-900">{{ $business->name }}</h1>
    <p class="text-sm text-gray-600">{{ $reconciliation->reconciliation_date->format('l, F j, Y') }} · {{ $reconciliation->user->name }}</p>
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-gray-200 p-4">
        <p class="text-xs uppercase text-gray-500">Total sales</p>
        <p class="mt-1 text-lg font-bold">@money($report['total_sales'])</p>
    </div>
    <div class="rounded-xl border border-red-100 bg-red-50 p-4">
        <p class="text-xs uppercase text-red-700">Expenses</p>
        <p class="mt-1 text-lg font-bold text-red-700">@money($report['total_expenses'])</p>
    </div>
    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
        <p class="text-xs uppercase text-amber-800">Damages</p>
        <p class="mt-1 text-lg font-bold text-amber-900">@money($report['total_damages'])</p>
    </div>
</div>

<div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-5">
    <h2 class="text-sm font-semibold text-indigo-950">Cash balancing</h2>
    <dl class="mt-4 space-y-2 text-sm">
        <div class="flex justify-between"><dt class="text-indigo-900/70">Expected cash</dt><dd class="font-semibold text-indigo-950">@money($reconciliation->expected_cash)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Actual cash</dt><dd class="font-medium">@money($reconciliation->actual_cash)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Mobile money</dt><dd class="font-medium">@money($reconciliation->actual_mobile_money)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Bank &amp; other</dt><dd class="font-medium">@money($reconciliation->actual_bank_other ?? 0)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Expenses</dt><dd class="font-medium text-red-700">@money($reconciliation->total_expenses ?? 0)</dd></div>
        <div class="flex justify-between"><dt class="text-indigo-900/70">Damages</dt><dd class="font-medium text-amber-800">@money($reconciliation->total_damages ?? 0)</dd></div>
        <div class="flex justify-between border-t border-indigo-200 pt-2">
            <dt class="font-semibold text-indigo-950">Missing money</dt>
            <dd class="font-bold {{ $missingMoney >= 0 ? 'text-emerald-700' : 'text-red-700' }}">@money($missingMoney)</dd>
        </div>
    </dl>
    <p class="mt-3 text-[11px] text-indigo-900/60">Expected cash = Actual cash + Mobile money + Bank &amp; other + Expenses + Damages + Missing money</p>
</div>

@if($reconciliation->notes)
    <div class="rounded-xl border border-gray-200 p-4 text-sm">
        <p class="text-xs font-semibold uppercase text-gray-500">Notes</p>
        <p class="mt-2 text-gray-700">{{ $reconciliation->notes }}</p>
    </div>
@endif
