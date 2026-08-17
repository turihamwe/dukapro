@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200',
        'error'   => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
        'info'    => 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm ' . ($styles[$type] ?? $styles['info'])]) }}>
    {{ $slot }}
</div>
