@props(['label', 'value', 'accent' => 'indigo'])

@php
    $accents = [
        'indigo' => 'from-indigo-500/10 to-indigo-600/5 border-indigo-100 dark:border-indigo-900',
        'emerald' => 'from-emerald-500/10 to-emerald-600/5 border-emerald-100 dark:border-emerald-900',
        'amber' => 'from-amber-500/10 to-amber-600/5 border-amber-100 dark:border-amber-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border bg-gradient-to-br p-5 shadow-sm ' . ($accents[$accent] ?? $accents['indigo']) . ' bg-white dark:bg-gray-900']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $value }}</p>
</div>
