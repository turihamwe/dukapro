@php
    /** @var string $period */
    /** @var string $routeName */
    /** @var \Carbon\Carbon $start */
    /** @var \Carbon\Carbon $end */
    $customFrom = request('from', $period === 'custom' ? $start->toDateString() : '');
    $customTo = request('to', $period === 'custom' ? $end->toDateString() : '');
    $periodPill = 'snap-start shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition min-h-[44px] inline-flex items-center';
    $periodActive = 'border-indigo-600 bg-indigo-600 text-white';
    $periodIdle = 'border-gray-200 bg-white text-gray-700 hover:border-gray-300';
@endphp

<div class="mb-6 flex flex-wrap items-center gap-2 pb-1">
    @foreach(\App\Support\ReportPeriodResolver::periods() as $key => $labelOption)
        <a href="{{ tenant_route($routeName, ['period' => $key]) }}"
           class="{{ $periodPill }} {{ $period === $key ? $periodActive : $periodIdle }}">
            {{ $labelOption }}
        </a>
    @endforeach
    <details class="relative shrink-0" @if($period === 'custom') open @endif>
        <summary class="{{ $periodPill }} {{ $period === 'custom' ? $periodActive : $periodIdle }} cursor-pointer list-none inline-flex items-center gap-1.5 [&::-webkit-details-marker]:hidden">
            Custom
            <svg class="h-3.5 w-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="absolute left-0 top-[calc(100%+0.35rem)] z-30 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
            <form method="GET" action="{{ tenant_route($routeName) }}" class="space-y-3">
                <input type="hidden" name="period" value="custom">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">From</label>
                        <input type="date" name="from" value="{{ $customFrom }}" required class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">To</label>
                        <input type="date" name="to" value="{{ $customTo }}" required class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>
                <x-button variant="primary" size="sm" type="submit" class="w-full">Apply</x-button>
            </form>
        </div>
    </details>
</div>
