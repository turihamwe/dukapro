@php
    $prevConversion = null;
    if ($stageKey === 'catalog') {
        $prevConversion = $conversions['registered_to_catalog'] ?? 0;
    } elseif ($stageKey === 'subscribed') {
        $prevConversion = $conversions['catalog_to_subscribed'] ?? 0;
    }

    if ($stageKey === 'catalog') {
        $colors = ['border' => 'border-indigo-200', 'badge' => 'bg-indigo-100 text-indigo-800', 'count' => 'text-indigo-700'];
    } elseif ($stageKey === 'subscribed') {
        $colors = ['border' => 'border-violet-200', 'badge' => 'bg-violet-100 text-violet-800', 'count' => 'text-violet-700'];
    } else {
        $colors = ['border' => 'border-slate-200', 'badge' => 'bg-slate-100 text-slate-800', 'count' => 'text-slate-800'];
    }
@endphp

<div class="flex flex-col rounded-xl border {{ $colors['border'] }} bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $colors['badge'] }}">
                Stage {{ $stageKey === 'registered' ? '1' : ($stageKey === 'catalog' ? '2' : '3') }}
            </span>
            <h3 class="mt-2 font-semibold text-gray-900">{{ $stage['label'] }}</h3>
            <p class="mt-1 text-xs text-gray-500">{{ $stage['description'] }}</p>
        </div>
        <a href="{{ $stage['view_route'] }}"
           class="shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-violet-700 transition hover:border-violet-300 hover:bg-violet-50">
            View →
        </a>
    </div>
    <p class="mt-4 text-3xl font-bold {{ $colors['count'] }}">{{ number_format($stage['count']) }}</p>
    @if($prevConversion !== null)
        <p class="mt-2 text-xs text-gray-500">
            <span class="font-semibold text-gray-700">{{ number_format($prevConversion, 1) }}%</span> conversion from previous stage
        </p>
    @endif
</div>
