@props(['period', 'routeName', 'extraParams' => []])

<div class="mb-6 flex gap-2 overflow-x-auto pb-1 -mx-1 px-1 snap-x snap-mandatory">
    @foreach(\App\Support\ReportPeriodResolver::periods() as $key => $labelOption)
        <a href="{{ tenant_route($routeName, array_merge($extraParams, ['period' => $key])) }}"
           class="snap-start shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition min-h-[44px] inline-flex items-center {{ $period === $key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300' }}">
            {{ $labelOption }}
        </a>
    @endforeach
</div>
