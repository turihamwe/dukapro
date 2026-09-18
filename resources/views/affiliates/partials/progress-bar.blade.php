@php
    $compact = $compact ?? false;
    $class = trim('min-w-[8.5rem] ' . ($class ?? ''));

    $statusColors = [
        'green' => 'bg-emerald-500',
        'yellow' => 'bg-amber-400',
        'red' => 'bg-rose-500',
    ];
    $badgeColors = [
        'green' => 'bg-emerald-100 text-emerald-800',
        'yellow' => 'bg-amber-100 text-amber-800',
        'red' => 'bg-rose-100 text-rose-800',
    ];
    $barColor = $statusColors[$horizon['status'] ?? 'red'] ?? 'bg-gray-400';
    $badgeColor = $badgeColors[$horizon['status'] ?? 'red'] ?? 'bg-gray-100 text-gray-700';
@endphp

<div class="{{ $class }}">
    <div class="flex items-center justify-between gap-2">
        <span class="{{ $compact ? 'text-[10px]' : 'text-xs' }} font-medium text-gray-600">{{ $label }}</span>
        <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $badgeColor }}">{{ $horizon['label'] ?? '' }}</span>
    </div>
    <div class="mt-1.5 flex items-center gap-2">
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
            <div class="{{ $barColor }} h-2 rounded-full transition-all duration-300" style="width: {{ $horizon['percent'] ?? 0 }}%"></div>
        </div>
        <span class="{{ $compact ? 'text-[10px]' : 'text-xs' }} tabular-nums text-gray-700">
            {{ number_format($horizon['actual'] ?? 0) }}/{{ rtrim(rtrim(number_format($horizon['target'] ?? 0, 2), '0'), '.') }}
        </span>
    </div>
</div>
